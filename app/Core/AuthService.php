<?php
namespace App\Core;
use App\Core\Database;
use PDO;
use Exception;
class AuthService {
   private static ?array $permisosUsuario = null;
   /**
    * Valida si el usuario en sesión tiene asignado un permiso específico
    */
   public static function tienePermiso(int $usuarioId, string $permisoSlug): bool {
       $db = Database::getConnection();
       // 1. Si es Administrador con rol es_admin = 1, tiene pase directo
       $stmtUser = $db->prepare("
           SELECT u.id, r.es_admin 
           FROM usuarios u 
           INNER JOIN roles r ON u.rol_id = r.id 
           WHERE u.id = :id AND u.estado = 1
       ");
       $stmtUser->execute(['id' => $usuarioId]);
       $user = $stmtUser->fetch();
       if (!$user) return false;
       if ((bool)$user['es_admin']) return true;
       // 2. Comprobar catálogo de permisos asignados al rol
       $stmtPerm = $db->prepare("
           SELECT COUNT(*) 
           FROM roles_permisos rp
           INNER JOIN permisos p ON rp.permiso_id = p.id
           INNER JOIN usuarios u ON u.rol_id = rp.rol_id
           WHERE u.id = :uid AND p.slug = :slug
       ");
       $stmtPerm->execute(['uid' => $usuarioId, 'slug' => $permisoSlug]);
       return (int)$stmtPerm->fetchColumn() > 0;
   }
   /**
    * Valida permisos y arroja excepción HTTP 403 si falla

    */
   public static function verificar(int $usuarioId, string $permisoSlug): void {
       if (!self::tienePermiso($usuarioId, $permisoSlug)) {
           http_response_code(403);
           header('Content-Type: application/json');
           echo json_encode([
               "status"  => "forbidden",
               "message" => "Acceso Denegado: Requiere el privilegio '{$permisoSlug}'."
           ]);
           exit;
       }
   }
   /**
    * Autorización por clave de supervisor (Override en caliente en POS)
    */
   public static function autorizarPorSupervisor(string $usuarioSupervisor, string $password, string $permisoSlug): bool {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT u.id, u.password, r.es_admin 
           FROM usuarios u
           INNER JOIN roles r ON u.rol_id = r.id
           WHERE u.usuario = :usr AND u.estado = 1
       ");
       $stmt->execute(['usr' => $usuarioSupervisor]);
       $sup = $stmt->fetch();
       if (!$sup || !password_verify($password, $sup['password'])) {
           return false;
       }
       return (bool)$sup['es_admin'] || self::tienePermiso((int)$sup['id'], $permisoSlug);
   }
}