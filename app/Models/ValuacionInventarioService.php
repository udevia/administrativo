<?php
namespace App\Models;
use App\Core\Database;
use PDO;
use Exception;
class ValuacionInventarioService {
   /**
    * Registra una nueva capa de costo al entrar mercancía por Compra o Ajuste Positivo
    */
   public static function registrarCapaEntrada(
       int $productoId,
       int $depositoId,
       string $docTipo,
       string $docNumero,

       float $cantidad,
       float $costoUnitario,
       ?int $loteId = null
   ): void {
       $db = Database::getConnection();
       
       $stmt = $db->prepare("
           INSERT INTO inventario_capas 
           (producto_id, deposito_id, documento_tipo, documento_numero, cantidad_inicial, cantidad_remanente, costo_uni
           VALUES (:p, :d, :dt, :dn, :ci, :cr, :costo, :lote, 'DISPONIBLE')
       ");
       
       $stmt->execute([
           'p'     => $productoId,
           'd'     => $depositoId,
           'dt'    => $docTipo,
           'dn'    => $docNumero,
           'ci'    => $cantidad,
           'cr'    => $cantidad,
           'costo' => $costoUnitario,
           'lote'  => $loteId
       ]);
   }
   /**
    * Resuelve el despacho y calcula el Costo de Ventas según FIFO, LIFO o FEFO
    */
   public static function despacharCapas(int $productoId, int $depositoId, float $cantidadRequerida): array {
       $db = Database::getConnection();
       // 1. Determinar método aplicable
       $stmtProd = $db->prepare("
           SELECT p.metodo_costeo, p.maneja_lotes, e.metodo_costeo_default 
           FROM productos p
           CROSS JOIN empresa e
           WHERE p.id = :id
           LIMIT 1
       ");
       $stmtProd->execute(['id' => $productoId]);
       $cfg = $stmtProd->fetch();
       $metodo = $cfg['metodo_costeo'];
       if ($metodo === 'DEFAULT' || empty($metodo)) {
           $metodo = $cfg['metodo_costeo_default'] ?? 'PROMEDIO_PONDERADO';
       }
       // Si maneja lotes y está en FEFO, delegar o priorizar FEFO
       if ($cfg['maneja_lotes'] && $metodo === 'FEFO') {
           return self::despacharFEFO($productoId, $depositoId, $cantidadRequerida);
       }
       // Determinar orden de capas SQL (FIFO = ASC, LIFO = DESC)
       $ordenSql = ($metodo === 'LIFO') ? 'DESC' : 'ASC';
       // 2. Consultar capas disponibles con bloqueo pesimista
       $stmtCapas = $db->prepare("
           SELECT id, cantidad_remanente, costo_unitario, lote_id 
           FROM inventario_capas
           WHERE producto_id = :p 
             AND deposito_id = :d 
             AND estado = 'DISPONIBLE'
             AND cantidad_remanente > 0
           ORDER BY fecha_ingreso {$ordenSql}, id {$ordenSql}
           FOR UPDATE
       ");
       $stmtCapas->execute(['p' => $productoId, 'd' => $depositoId]);
       $capas = $stmtCapas->fetchAll();
       $porDespachar = $cantidadRequerida;
       $costoTotalConsumido = 0.0;
       $capasConsumidas = [];
       foreach ($capas as $capa) {
           if ($porDespachar <= 0) break;

           $disponibleCapa = (float)$capa['cantidad_remanente'];
           $tomar = min($porDespachar, $disponibleCapa);
           $costoUnit = (float)$capa['costo_unitario'];
           // Actualizar remanente de la capa
           $db->prepare("
               UPDATE inventario_capas 
               SET cantidad_remanente = cantidad_remanente - :cant,
                   estado = IF(cantidad_remanente - :cant2 <= 0.0001, 'AGOTADO', 'DISPONIBLE')
               WHERE id = :id
           ")->execute(['cant' => $tomar, 'cant2' => $tomar, 'id' => $capa['id']]);
           $costoTotalConsumido += ($tomar * $costoUnit);
           $capasConsumidas[] = [
               'capa_id'        => $capa['id'],
               'cantidad'       => $tomar,
               'costo_unitario' => $costoUnit,
               'lote_id'        => $capa['lote_id']
           ];
           $porDespachar -= $tomar;
       }
       if ($porDespachar > 0.0001) {
           throw new Exception("Stock insuficiente en capas disponibles para cubrir {$cantidadRequerida} unidades.");
       }
       $costoUnitarioPonderado = $costoTotalConsumido / $cantidadRequerida;
       return [
           'metodo_utilizado' => $metodo,
           'cantidad_total'   => $cantidadRequerida,
           'costo_total'      => round($costoTotalConsumido, 4),
           'costo_unitario'   => round($costoUnitarioPonderado, 4),
           'detalles_capas'   => $capasConsumidas
       ];
   }
   /**
    * Despacho por FEFO consultando lotes
    */
   private static function despacharFEFO(int $productoId, int $depositoId, float $cantidadRequerida): array {
       $lotes = LoteService::despacharLotesFEFO($productoId, $depositoId, $cantidadRequerida);
       $db = Database::getConnection();
       
       // Obtener último costo o costo promedio para la transacción
       $stmt = $db->prepare("SELECT costo_promedio FROM productos WHERE id = :p");
       $stmt->execute(['p' => $productoId]);
       $costoPromedio = (float)$stmt->fetchColumn();
       return [
           'metodo_utilizado' => 'FEFO',
           'cantidad_total'   => $cantidadRequerida,
           'costo_total'      => round($cantidadRequerida * $costoPromedio, 4),
           'costo_unitario'   => $costoPromedio,
           'detalles_lotes'   => $lotes
       ];
   }
}