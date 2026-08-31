<?php
namespace App\Models;
use App\Core\Database;
use PDO;
use Exception;
class Cliente {
   public static function all(): array {
       $db = Database::getConnection();
       $stmt = $db->query("
           SELECT c.*, z.descripcion AS zona, v.nombre AS vendedor
           FROM clientes c
           INNER JOIN zonas z ON c.zona_id = z.id
           LEFT JOIN vendedores v ON c.vendedor_id = v.id
           WHERE c.estado = 1
           ORDER BY c.razon_social ASC

       ");
       return $stmt->fetchAll();
   }
   public static function find(int $id): ?array {
       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
       $stmt->execute(['id' => $id]);
       $row = $stmt->fetch();
       return $row ?: null;
   }
   public static function validarCredito(int $clienteId, float $montoNuevaOperacion): bool {
       $cliente = self::find($clienteId);
       if (!$cliente) {
           throw new Exception("El cliente especificado no existe.");
       }
       if (!$cliente['permite_credito']) {
           throw new Exception("El cliente '{$cliente['razon_social']}' no tiene crédito comercial habilitado.");
       }
       $saldoActual = (float)$cliente['saldo_actual'];
       $limiteCredito = (float)$cliente['limite_credito'];
       $nuevoSaldo = $saldoActual + $montoNuevaOperacion;
       if ($limiteCredito > 0 && $nuevoSaldo > $limiteCredito) {
           $excedente = $nuevoSaldo - $limiteCredito;
           throw new Exception("Límite de crédito excedido. Disponible: " . ($limiteCredito - $saldoActual) . ", Intentado: {$montoNuevaOperacion}");
       }
       return true;
   }
   public static function actualizarSaldo(int $clienteId, float $monto, string $tipoOperacion = 'CARGO'): void {
       $db = Database::getConnection();
       $signo = ($tipoOperacion === 'CARGO') ? '+' : '-';
       $stmt = $db->prepare("UPDATE clientes SET saldo_actual = saldo_actual {$signo} :monto WHERE id = :id");
       $stmt->execute(['monto' => $monto, 'id' => $clienteId]);
   }
}