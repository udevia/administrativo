<?php
namespace App\Core;
use App\Core\Database;
use PDO;
class AuditLogger {
   /**
    * Inserta un evento en la bitácora inmutable
    */
   public static function log(
       string $modulo,
       string $evento,
       string $descripcion,
       ?string $tabla = null,
       ?string $registroId = null,
       ?array $payloadAnterior = null,
       ?array $payloadNuevo = null,
       ?int $usuarioId = null,
       ?int $supervisorId = null
   ): void {
       try {
           $db = Database::getConnection();
           $sessionUser = Session::user();
           $uid = $usuarioId ?? (int)($sessionUser['id'] ?? 1);
           $ip  = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
           $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System', 0, 255);
           $stmt = $db->prepare("
               INSERT INTO auditoria_logs (
                   usuario_id, supervisor_id, modulo, evento, tabla_afectada,
                   registro_id, descripcion, payload_anterior, payload_nuevo, ip_origen, user_agent
               ) VALUES (
                   :uid, :sup, :mod, :eve, :tab,
                   :reg, :desc, :prev, :post, :ip, :ua
               )
           ");
           $stmt->execute([
               'uid'  => $uid,
               'sup'  => $supervisorId,
               'mod'  => $modulo,
               'eve'  => $evento,
               'tab'  => $tabla,
               'reg'  => $registroId,
               'desc' => $descripcion,
               'prev' => $payloadAnterior ? json_encode($payloadAnterior, JSON_UNESCAPED_UNICODE) : null,
               'post' => $payloadNuevo ? json_encode($payloadNuevo, JSON_UNESCAPED_UNICODE) : null,
               'ip'   => $ip,
               'ua'   => $ua
           ]);
       } catch (\Throwable $e) {
           // Evitar que un fallo en el log bloquee la transacción comercial principal
           error_log("Fallo escribiendo en bitácora de auditoría: " . $e->getMessage());
       }
   }
}