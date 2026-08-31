<?php
namespace App\Models;
use App\Core\Database;
use PDO;
class ComisionesService {
   /**
    * Liquida comisiones generadas por Venta o Cobro
    */
   public static function registrarComisionVenta(int $ventaId): void {

       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT * FROM ventas WHERE id = :id");
       $stmt->execute(['id' => $ventaId]);
       $venta = $stmt->fetch();
       if (!$venta || empty($venta['vendedor_id'])) return;
       $vendedorId = (int)$venta['vendedor_id'];
       // Obtener detalles con sus respectivas categorías
       $stmtDet = $db->prepare("
           SELECT vd.*, p.categoria_id, p.departamento_id
           FROM ventas_detalles vd
           INNER JOIN productos p ON vd.producto_id = p.id
           WHERE vd.venta_id = :v
       ");
       $stmtDet->execute(['v' => $ventaId]);
       $detalles = $stmtDet->fetchAll();
       // Obtener reglas del vendedor
       $stmtReglas = $db->prepare("SELECT * FROM vendedores_comisiones_reglas WHERE vendedor_id = :v");
       $stmtReglas->execute(['v' => $vendedorId]);
       $reglas = $stmtReglas->fetchAll();
       // Obtener comisión base del vendedor por si no hay regla específica
       $stmtVend = $db->prepare("SELECT comision_ventas FROM vendedores WHERE id = :v");
       $stmtVend->execute(['v' => $vendedorId]);
       $comisionBase = (float)$stmtVend->fetchColumn();
       $stmtIns = $db->prepare("
           INSERT INTO vendedores_comisiones_historico 
           (vendedor_id, tipo_origen, documento_id, numero_documento, cliente_id, base_calculo, porcentaje_aplicado, mo
           VALUES (:v, 'VENTA_DIRECTA', :doc_id, :doc_num, :cli, :base, :porc, :monto, :fecha)
       ");
       foreach ($detalles as $d) {
           $porcentaje = $comisionBase;
           // Buscar si la categoría tiene regla especial
           foreach ($reglas as $r) {
               if ($r['categoria_id'] == $d['categoria_id'] && $r['porcentaje_comision_venta'] > 0) {
                   $porcentaje = (float)$r['porcentaje_comision_venta'];
                   break;
               }
           }
           $baseLinea = (float)$d['subtotal'];
           $montoComision = $baseLinea * ($porcentaje / 100);
           if ($montoComision > 0) {
               $stmtIns->execute([
                   'v'       => $vendedorId,
                   'doc_id'  => $ventaId,
                   'doc_num' => $venta['numero_documento'],
                   'cli'     => $venta['cliente_id'],
                   'base'    => $baseLinea,
                   'porc'    => $porcentaje,
                   'monto'   => round($montoComision, 4),
                   'fecha'   => $venta['fecha_emision']
               ]);
           }
       }
   }
}