<?php
namespace App\Models;
use App\Core\Database;
use PDO;
class Proveedor {
   public static function all(): array {
       $db = Database::getConnection();
       $stmt = $db->query("SELECT * FROM proveedores WHERE estado = 1 ORDER BY razon_social ASC");
       return $stmt->fetchAll();
   }
   public static function find(int $id): ?array {
       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT * FROM proveedores WHERE id = :id");
       $stmt->execute(['id' => $id]);
       $row = $stmt->fetch();
       return $row ?: null;
   }
   public static function actualizarSaldo(int $proveedorId, float $monto, string $tipoOperacion = 'CARGO'): void {
       $db = Database::getConnection();
       $signo = ($tipoOperacion === 'CARGO') ? '+' : '-';
       $stmt = $db->prepare("UPDATE proveedores SET saldo_actual = saldo_actual {$signo} :monto WHERE id = :id");
       $stmt->execute(['monto' => $monto, 'id' => $proveedorId]);
   }
}
