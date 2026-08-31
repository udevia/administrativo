<?php
namespace App\Core;
use App\Core\Database;
use PDO;
use Exception;
class BankGatewayEngine {
   /**
    * Procesa Débito Inmediato (C2P) solicitando el token/OTP al pagador
    */
   public static function procesarDebitoInmediato(string $bancoCodigo, array $datosPago): array {
       $db = Database::getConnection();
       
       $stmt = $db->prepare("SELECT * FROM pasarelas_bancarias_config WHERE banco_codigo = :b AND estado = 1 LIMIT 1");
       $stmt->execute(['b' => $bancoCodigo]);
       $config = $stmt->fetch();
        if (!$config) {
            throw new Exception("La pasarela bancaria para el banco {$bancoCodigo} no está configurada o se encuentra inactiva.");
        }
       $url = rtrim($config['api_url_base'], '/') . '/payment/c2p';
       
       // Estructura estándar C2P SUDEBAN / Banesco / Plaza
       $payload = [
           'comercio' => [
               'rif'      => $config['comercio_rif'],
               'telefono' => $config['comercio_telefono']
           ],
           'pagador' => [
               'banco'    => $datosPago['banco_origen'],
               'telefono' => $datosPago['telefono_pagador'],
               'cedula'   => $datosPago['cedula_pagador'],
               'token'    => $datosPago['token_otp'] // Clave dinámica generada por el usuario
           ],
           'transaccion' => [
               'monto'            => (float)$datosPago['monto_bs'],
               'concepto'         => "Pago Orden Web " . ($datosPago['numero_orden'] ?? 'S/N'),
               'referencia_unica' => 'REF-' . date('YmdHis') . rand(10, 99)
           ]
       ];
       $headers = [
           'Content-Type: application/json',
           'X-Client-Id: ' . $config['client_id'],

           'X-Client-Secret: ' . $config['client_secret'],
           'X-Signature: ' . hash_hmac('sha256', json_encode($payload), $config['client_secret'])
       ];
       // Ejecutar llamada cURL
       $ch = curl_init($url);
       curl_setopt_array($ch, [
           CURLOPT_POST           => true,
           CURLOPT_POSTFIELDS     => json_encode($payload),
           CURLOPT_HTTPHEADER     => $headers,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_TIMEOUT        => 30,
           CURLOPT_SSL_VERIFYPEER => ($config['ambiente'] === 'PRODUCCION')
       ]);
       $response = curl_exec($ch);
       $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       $curlError = curl_error($ch);
       curl_close($ch);
       if ($curlError) {
           throw new Exception("Error de red conectando con el API bancaria: " . $curlError);
       }
       $resJson = json_decode($response, true);
       // Registro de Auditoría
       $aprobado = ($httpCode === 200 && isset($resJson['codigo_respuesta']) && $resJson['codigo_respuesta'] === '00');
       $referenciaBanco = $resJson['referencia_bancaria'] ?? $payload['transaccion']['referencia_unica'];
       $stmtLog = $db->prepare("
           INSERT INTO pasarelas_transacciones_log (
               pedido_web_id, banco_codigo, tipo_operacion, telefono_pagador, cedula_pagador,
               referencia_bancaria, monto_bs, codigo_autorizacion, codigo_respuesta,
               mensaje_respuesta, raw_payload_request, raw_payload_response, estado
           ) VALUES (
               :ped, :ban, 'C2P_DEBITO_INMEDIATO', :tel, :ced,
               :ref, :monto, :aut, :cod,
               :msg, :req, :res, :est
           )
       ");
       $stmtLog->execute([
           'ped'   => (int)$datosPago['pedido_web_id'],
           'ban'   => $bancoCodigo,
           'tel'   => $datosPago['telefono_pagador'],
           'ced'   => $datosPago['cedula_pagador'],
           'ref'   => $referenciaBanco,
           'monto' => (float)$datosPago['monto_bs'],
           'aut'   => $resJson['codigo_autorizacion'] ?? null,
           'cod'   => $resJson['codigo_respuesta'] ?? (string)$httpCode,
           'msg'   => $resJson['mensaje'] ?? ($aprobado ? 'Aprobado' : 'Transacción Denegada'),
           'req'   => json_encode($payload),
           'res'   => $response,
           'est'   => $aprobado ? 'APROBADO' : 'RECHAZADO'
       ]);
        if (!$aprobado) {
            throw new Exception("Pago denegado por el banco: " . ($resJson['mensaje'] ?? 'Fondos insuficientes o Token OTP inválido.'));
        }
       // Asentar cobro en el pedido web y en tesorería
       self::confirmarCobroAutomatico(
           (int)$datosPago['pedido_web_id'],
           (int)$config['cuenta_bancaria_id'],
           $referenciaBanco,
           (float)$datosPago['monto_bs'],
           (float)$datosPago['tasa_cambio']
       );
       return [
           'status'              => 'success',
           'referencia_bancaria' => $referenciaBanco,
           'codigo_autorizacion' => $resJson['codigo_autorizacion'] ?? 'AUT-' . rand(1000, 9999),
           'mensaje'             => 'Pago validado y debitado exitosamente en cuenta bancaria.'

       ];
   }
   /**
    * Asienta la validación exitosa: actualiza el pedido web, la caja bancaria y deja listo para POS
    */
    private static function confirmarCobroAutomatico(int $pedidoWebId, int $cuentaBancariaId, string $referencia, float $montoBs, float $tasaCambio): void {
        $db = Database::getConnection();
       // 1. Marcar el pedido web como confirmado
       $stmt = $db->prepare("
           UPDATE ecommerce_pedidos 
           SET estado_pago = 'VERIFICADO', referencia_pago = :ref, estado_despacho = 'EN_PREPARACION'
           WHERE id = :id
       ");
       $stmt->execute(['ref' => $referencia, 'id' => $pedidoWebId]);
       // 2. Registrar movimiento en Tesorería / Cuentas Bancarias
       $stmtMov = $db->prepare("
           INSERT INTO movimientos_bancarios 
           (cuenta_id, tipo_movimiento, numero_referencia, monto, fecha, conciliado, beneficiario_concepto, usuario_id)
           VALUES (:cta, 'DEPOSITO', :ref, :monto, CURDATE(), 1, :conc, 1)
       ");
       $montoUsd = $montoBs / $tasaCambio;
       $stmtMov->execute([
           'cta'   => $cuentaBancariaId,
           'ref'   => $referencia,
           'monto' => $montoUsd,
           'conc'  => "Cobro automático E-commerce C2P Ref: {$referencia}"
       ]);
       // Actualizar saldo bancario en el sistema
       $db->prepare("UPDATE cuentas_bancarias SET saldo_actual = saldo_actual + :m WHERE id = :id")
          ->execute(['m' => $montoUsd, 'id' => $cuentaBancariaId]);
   }
}