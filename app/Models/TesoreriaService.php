<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Models\Cliente;
use Exception;
use PDO;

class TesoreriaService {
    public static function procesarCobranza(array $cabecera, array $facturasAbonar, array $formasPago): array {
        $db = Database::getConnection();
        try {
            Database::beginTransaction();
            $clienteId   = (int)$cabecera['cliente_id'];
            $montoTotal  = (float)$cabecera['monto_total'];
            $fecha       = $cabecera['fecha'] ?? date('Y-m-d');
            $usuarioId   = (int)($cabecera['usuario_id'] ?? 1);
            $numRecibo   = $cabecera['numero_recibo'];

            // 1. Validar sumatorias
            $sumaFacturas = array_sum(array_column($facturasAbonar, 'monto_abonado'));
            $sumaPagos    = array_sum(array_column($formasPago, 'monto'));
            if (round($sumaFacturas, 4) !== round($montoTotal, 4) || round($sumaPagos, 4) !== round($montoTotal, 4)) {
                 throw new Exception("El monto total del recibo no coincide con los abonos o las formas de pago ingresadas.");
            }

            // 2. Insertar Recibo de Cobranza
            $stmt = $db->prepare("
                INSERT INTO recibos_cobranza (numero_recibo, cliente_id, fecha, moneda_id, tasa_cambio, monto_total, usuario_id, nota)
                VALUES (:num, :cli, :fec, :mon, :tasa, :tot, :usr, :nota)
            ");
            $stmt->execute([
                'num'  => $numRecibo,
                'cli'  => $clienteId,
                'fec'  => $fecha,
                'mon'  => (int)($cabecera['moneda_id'] ?? 1),
                'tasa' => (float)($cabecera['tasa_cambio'] ?? 1.0),
                'tot'  => $montoTotal,
                'usr'  => $usuarioId,
                'nota' => $cabecera['nota'] ?? 'Cobro a cliente'
            ]);
            $reciboId = (int)$db->lastInsertId();

            // 3. Aplicar abonos a cada Factura y asentar en CxC
            $stmtDetalle = $db->prepare("INSERT INTO recibos_cobranza_detalles (recibo_id, venta_id, monto_abonado) VALUES (:rec, :vta, :monto)");
            $stmtCxC = $db->prepare("
                INSERT INTO cuentas_por_cobrar (venta_id, cliente_id, tipo_transaccion, numero_referencia, monto, fecha_movimiento, usuario_id, concepto)
                VALUES (:vta, :cli, 'ABONO_PAGO', :ref, :monto, :fec, :usr, :nota)
            ");
            $stmtUpdateVenta = $db->prepare("
                UPDATE ventas 
                SET saldo_pendiente = saldo_pendiente - :abono,
                    estado = IF(saldo_pendiente - :abono2 <= 0.0001, 'PAGADA', 'PENDIENTE')
                WHERE id = :vta
            ");
            foreach ($facturasAbonar as $fa) {
                $ventaId = (int)$fa['venta_id'];
                $abono   = (float)$fa['monto_abonado'];
                $stmtDetalle->execute(['rec' => $reciboId, 'vta' => $ventaId, 'monto' => $abono]);
                $stmtCxC->execute([
                    'vta'   => $ventaId,
                    'cli'   => $clienteId,
                    'ref'   => $numRecibo,
                    'monto' => $abono,
                    'fec'   => $fecha,
                    'usr'   => $usuarioId,
                    'nota'  => "Abono según Recibo {$numRecibo}"
                ]);
                $stmtUpdateVenta->execute(['abono' => $abono, 'abono2' => $abono, 'vta' => $ventaId]);
            }

            // 4. Registrar ingresos en Bancos / Cajas
            $stmtFP = $db->prepare("INSERT INTO recibos_cobranza_formas_pago (recibo_id, cuenta_id, forma_pago, referencia, monto) VALUES (:rec, :cta, :fp, :ref, :monto)");
            $stmtMovBanco = $db->prepare("
                INSERT INTO movimientos_bancarios (cuenta_id, tipo_movimiento, numero_referencia, monto, tasa_cambio, fecha_movimiento, concepto, usuario_id)
                VALUES (:cta, 'COBRO_CLIENTE', :ref, :monto, :tasa, :fec, :conc, :usr)
            ");
            $stmtUpdCuenta = $db->prepare("UPDATE cuentas_bancarias SET saldo_actual = saldo_actual + :monto WHERE id = :cta");
            foreach ($formasPago as $fp) {
                $cuentaId = (int)$fp['cuenta_id'];
                $montoFP  = (float)$fp['monto'];
                $stmtFP->execute([
                    'rec'   => $reciboId,
                    'cta'   => $cuentaId,
                    'fp'    => $fp['forma_pago'],
                    'ref'   => $fp['referencia'] ?? '',
                    'monto' => $montoFP
                ]);
                $stmtMovBanco->execute([
                    'cta'   => $cuentaId,
                    'ref'   => $fp['referencia'] ?? $numRecibo,
                    'monto' => $montoFP,
                    'tasa'  => (float)($cabecera['tasa_cambio'] ?? 1.0),
                    'fec'   => $fecha,
                    'conc'  => "Cobro cliente Recibo: {$numRecibo}",
                    'usr'   => $usuarioId
                ]);
                $stmtUpdCuenta->execute(['monto' => $montoFP, 'cta' => $cuentaId]);
            }

            // 5. Rebajar saldo global de deuda del Cliente
            Cliente::actualizarSaldo($clienteId, $montoTotal, 'ABONO');
            Database::commit();
            return [
                'status'    => 'success',
                'recibo_id' => $reciboId,
                'monto'     => $montoTotal
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}