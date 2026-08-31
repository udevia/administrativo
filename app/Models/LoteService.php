<?php
namespace App\Models;
use App\Core\Database;
use Exception;
class LoteService {
   /**
    * Registra o incrementa stock de un lote en compras/recepciones
    */
   public static function ingresarStockLote(
       int $productoId,
       int $depositoId,
       string $numeroLote,
       string $fechaVencimiento,
       float $cantidad,
       ?string $fechaFabricacion = null
   ): int {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT id, existencia FROM producto_lotes 
           WHERE producto_id = :p AND deposito_id = :d AND numero_lote = :lote 
           FOR UPDATE
       ");
       $stmt->execute(['p' => $productoId, 'd' => $depositoId, 'lote' => $numeroLote]);
       $lote = $stmt->fetch();
       if ($lote) {
           $stmtUpd = $db->prepare("
               UPDATE producto_lotes 
               SET existencia = existencia + :cant, estado = 'ACTIVO' 
               WHERE id = :id
           ");
           $stmtUpd->execute(['cant' => $cantidad, 'id' => $lote['id']]);
           return (int)$lote['id'];
       } else {
           $stmtIns = $db->prepare("
               INSERT INTO producto_lotes (
                   producto_id, deposito_id, numero_lote, fecha_fabricacion, 
                   fecha_vencimiento, existencia, estado
               ) VALUES (
                   :p, :d, :lote, :ffab, :fvenc, :cant, 'ACTIVO'
               )
           ");
           $stmtIns->execute([
               'p'     => $productoId,
               'd'     => $depositoId,
               'lote'  => $numeroLote,
               'ffab'  => $fechaFabricacion,
               'fvenc' => $fechaVencimiento,
               'cant'  => $cantidad
           ]);
           return (int)$db->lastInsertId();
       }
   }
   /**
    * Despacho automático FEFO (First Expired, First Out) al facturar
    */
   public static function despacharLotesFEFO(int $productoId, int $depositoId, float $cantidadRequerida): array {

       $db = Database::getConnection();
       // Buscar lotes activos no vencidos ordenados por fecha de vencimiento más próxima
       $stmt = $db->prepare("
           SELECT id, numero_lote, fecha_vencimiento, (existencia - existencia_comprometida) AS disponible
           FROM producto_lotes
           WHERE producto_id = :p 
             AND deposito_id = :d 
             AND estado = 'ACTIVO'
             AND fecha_vencimiento >= CURDATE()
             AND (existencia - existencia_comprometida) > 0
           ORDER BY fecha_vencimiento ASC
           FOR UPDATE
       ");
       $stmt->execute(['p' => $productoId, 'd' => $depositoId]);
       $lotesDisponibles = $stmt->fetchAll();
       $porDespachar = $cantidadRequerida;
       $lotesAsignados = [];
       foreach ($lotesDisponibles as $lote) {
           if ($porDespachar <= 0) break;
           $disponibleLote = (float)$lote['disponible'];
           $tomar = min($porDespachar, $disponibleLote);
           // Descontar del lote
           $db->prepare("
               UPDATE producto_lotes 
               SET existencia = existencia - :cant,
                   estado = IF(existencia - :cant2 <= 0.0001, 'AGOTADO', 'ACTIVO')
               WHERE id = :id
           ")->execute(['cant' => $tomar, 'cant2' => $tomar, 'id' => $lote['id']]);
           $lotesAsignados[] = [
               'lote_id'           => $lote['id'],
               'numero_lote'       => $lote['numero_lote'],
               'fecha_vencimiento' => $lote['fecha_vencimiento'],
               'cantidad'          => $tomar
           ];
           $porDespachar -= $tomar;
       }
       if ($porDespachar > 0.0001) {
           throw new Exception("Stock insuficiente en lotes válidos no vencidos. Faltan {$porDespachar} unidades.");
       }
       return $lotesAsignados;
   }
}