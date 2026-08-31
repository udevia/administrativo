<?php
namespace App\Models;
use App\Core\Database;
use PDO;
use Exception;
class ContadorService {
   /**
    * Valida si un mes contable está bloqueado/auditado
    */
   public static function validarPeriodoAbierto(int $empresaId, string $fecha): void {
       $db = Database::getConnection();
       $mes = (int)date('m', strtotime($fecha));
       $ano = (int)date('Y', strtotime($fecha));
       $stmt = $db->prepare("SELECT estado FROM contabilidad_periodos WHERE empresa_id = :emp AND ano = :ano AND mes = :mes");
       $stmt->execute(['emp' => $empresaId, 'ano' => $ano, 'mes' => $mes]);
       $estado = $stmt->fetchColumn();

       if ($estado === 'CERRADO_AUDITADO') {
            throw new Exception("El período {$mes}/{$ano} está CERRADO y auditado por el contador. No se permiten modificaciones.");
       }
   }
   /**
    * Modificación directa de un comprobante por el contador (Reclasificación de cuentas / importes)
    */
   public static function ajustarComprobante(int $comprobanteId, array $datosCabecera, array $nuevosAsientos, int $contadorId) {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           // 1. Obtener comprobante
           $stmt = $db->prepare("SELECT * FROM contabilidad_comprobantes WHERE id = :id FOR UPDATE");
           $stmt->execute(['id' => $comprobanteId]);
           $comp = $stmt->fetch();
           if (!$comp) {
               throw new Exception("Comprobante contable no encontrado.");
           }
           // Validar que el periodo esté abierto
           self::validarPeriodoAbierto((int)$comp['empresa_id'], $datosCabecera['fecha'] ?? $comp['fecha']);
           // 2. Verificar Partida Doble
           $totalDebe = 0.0;
           $totalHaber = 0.0;
           foreach ($nuevosAsientos as $a) {
               $totalDebe += (float)$a['debe'];
               $totalHaber += (float)$a['haber'];
           }
           if (abs($totalDebe - $totalHaber) > 0.001) {
               throw new Exception("El comprobante está descuadrado: Debe ($totalDebe) != Haber ($totalHaber).");
           }
           // 3. Bitácora de modificación
           $bitacora = ($comp['auditoria_modificacion'] ?? '') . "\n[" . date('Y-m-d H:i:s') . "] Modificado por Contador (ID: {$contadorId})";
           // 4. Actualizar Cabecera
           $stmtUpd = $db->prepare("
               UPDATE contabilidad_comprobantes 
               SET fecha = :fec, concepto_general = :conc, total_debe = :debe, total_haber = :haber,
                   es_modificado_por_contador = 1, contador_usuario_id = :cnt, auditoria_modificacion = :bit
               WHERE id = :id
           ");
           $stmtUpd->execute([
               'fec'   => $datosCabecera['fecha'] ?? $comp['fecha'],
               'conc'  => $datosCabecera['concepto_general'] ?? $comp['concepto_general'],
               'debe'  => $totalDebe,
               'haber' => $totalHaber,
               'cnt'   => $contadorId,
               'bit'   => $bitacora,
               'id'    => $comprobanteId
           ]);
           // 5. Reemplazar Renglones
           $db->prepare("DELETE FROM contabilidad_asientos_detalles WHERE comprobante_id = :id")->execute(['id' => $comprobanteId]);
           $stmtIns = $db->prepare("
               INSERT INTO contabilidad_asientos_detalles (comprobante_id, cuenta_id, descripcion_renglon, debe, haber)
               VALUES (:comp, :cta, :desc, :debe, :haber)
           ");
           foreach ($nuevosAsientos as $a) {
               $stmtIns->execute([
                   'comp'  => $comprobanteId,
                   'cta'   => (int)$a['cuenta_id'],
                   'desc'  => $a['descripcion_renglon'],
                   'debe'  => (float)$a['debe'],
                   'haber' => (float)$a['haber']
               ]);

           }
           Database::commit();
           return ['status' => 'success', 'mensaje' => 'Comprobante auditado y actualizado correctamente.'];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
   /**
    * Cierre definitivo mensual de período fiscal
    */
   public static function cerrarPeriodoFiscal(int $empresaId, int $ano, int $mes, int $contadorId): void {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           INSERT INTO contabilidad_periodos (empresa_id, ano, mes, estado, cerrado_por_usuario_id, fecha_cierre)
           VALUES (:emp, :ano, :mes, 'CERRADO_AUDITADO', :usr, NOW())
           ON DUPLICATE KEY UPDATE estado = 'CERRADO_AUDITADO', cerrado_por_usuario_id = VALUES(cerrado_por_usuario_id)
       ");
       $stmt->execute(['emp' => $empresaId, 'ano' => $ano, 'mes' => $mes, 'usr' => $contadorId]);
   }
}