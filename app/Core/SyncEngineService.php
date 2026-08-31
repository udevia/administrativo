<?php
namespace App\Core;
use App\Core\Database;
use PDO;
use Exception;
class SyncEngineService {
   /**
    * Encola un evento transaccional (ejecutado por los modelos tras INSERT/UPDATE/DELETE)
    */
   public static function encolarEvento(string $tabla, string $operacion, string $registroId, array $datos): void {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           INSERT INTO sync_queue (tabla_afectada, operacion, registro_id, payload_json, estado)
           VALUES (:t, :op, :rid, :data, 'PENDIENTE')
       ");
       $stmt->execute([
           't'    => $tabla,
           'op'   => $operacion,
           'rid'  => $registroId,
           'data' => json_encode($datos, JSON_UNESCAPED_UNICODE)
       ]);
   }
   /**
    * Procesa la cola y replica hacia la base de datos en la nube
    */
   public static function sincronizarConNube(): array {
       $db = Database::getConnection();
       // 1. Obtener configuración
       $cfg = $db->query("SELECT * FROM configuracion_sync WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
       if (!$cfg || !$cfg['habilitar_sync_nube']) {
           return ['status' => 'disabled', 'mensaje' => 'Sincronización deshabilitada.'];
       }
       // 2. Comprobar conexión con la nube (Direct DB o API Token)
       try {
           if (!empty($cfg['db_replica_host'])) {
                $dsn = "mysql:host={$cfg['db_replica_host']};port={$cfg['db_replica_port']};dbname={$cfg['db_replica_name']};charset=utf8mb4";
               $cloudDb = new PDO($dsn, $cfg['db_replica_user'], $cfg['db_replica_pass'], [
                   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                   PDO::ATTR_TIMEOUT => 5
               ]);
           } else {
               return ['status' => 'error', 'mensaje' => 'No hay conexión o endpoint remoto configurado.'];
           }
       } catch (Exception $e) {
           return ['status' => 'offline', 'mensaje' => 'Sin conexión al servidor nube: ' . $e->getMessage()];
       }
       // 3. Tomar lote de 100 eventos pendientes
       $stmtQueue = $db->query("SELECT * FROM sync_queue WHERE estado = 'PENDIENTE' ORDER BY id ASC LIMIT 100");
       $eventos = $stmtQueue->fetchAll(PDO::FETCH_ASSOC);
       $procesados = 0;
       foreach ($eventos as $ev) {
           $data = json_decode($ev['payload_json'], true);
           $tabla = $ev['tabla_afectada'];

           try {
               if ($ev['operacion'] === 'INSERT' || $ev['operacion'] === 'UPDATE') {
                   // Sincronización UPSERT en la nube
                   $cols = array_keys($data);
                   $colsSql = implode("`, `", $cols);
                   $paramsSql = implode(", ", array_map(fn($c) => ":{$c}", $cols));
                   $updateSql = implode(", ", array_map(fn($c) => "`{$c}` = VALUES(`{$c}`)", $cols));
                    $sql = "INSERT INTO `{$tabla}` (`{$colsSql}`) VALUES ({$paramsSql}) ON DUPLICATE KEY UPDATE {$updateSql}";
                   $stmtCloud = $cloudDb->prepare($sql);
                   $stmtCloud->execute($data);
               } elseif ($ev['operacion'] === 'DELETE') {
                   $stmtCloud = $cloudDb->prepare("DELETE FROM `{$tabla}` WHERE id = :id");
                   $stmtCloud->execute(['id' => $ev['registro_id']]);
               }
               // Marcar como sincronizado
               $db->prepare("UPDATE sync_queue SET estado = 'SINCRONIZADO', sincronizado_at = NOW() WHERE id = :id")
                  ->execute(['id' => $ev['id']]);
               $procesados++;
           } catch (Exception $ex) {
                $db->prepare("UPDATE sync_queue SET estado = 'ERROR', intentos = intentos + 1, ultimo_error = :err WHERE id = :id")
                  ->execute(['id' => $ev['id'], 'err' => $ex->getMessage()]);
           }
       }
       // Actualizar ping
       $db->exec("UPDATE configuracion_sync SET ultimo_ping_exitoso = NOW() WHERE id = 1");
       return [
           'status'     => 'success',
           'procesados' => $procesados,
           'pendientes' => count($eventos) - $procesados
       ];
   }
}