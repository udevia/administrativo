<?php
namespace App\Models;
use App\Core\Database;
use PDO;
class AprovisionamientoService {
   /**
    * Calcula la demanda histórica y genera la sugerencia de compra por producto
    */
    public static function calcularSugerenciaCompras(int $diasHistorico = 30, ?int $proveedorId = null, ?int $categoriaId = null): array {
       $db = Database::getConnection();
       $sql = "
           SELECT 
               p.id,
               p.codigo,
               p.descripcion,
               p.costo_ultimo,
               p.costo_promedio,
               p.modo_calculo_stock,
               p.stock_minimo AS stock_minimo_manual,
               p.stock_maximo AS stock_maximo_manual,
               p.lead_time_dias,
               p.dias_seguridad_stock,
               p.dias_cobertura_maxima,
               p.proveedor_habitual_id,
               pr.razon_social AS proveedor_nombre,
               COALESCE(SUM(pd.existencia), 0) AS stock_fisico,
               COALESCE(SUM(pd.existencia_comprometida), 0) AS stock_comprometido,
               COALESCE(SUM(pd.existencia_por_llegar), 0) AS stock_en_transito,
               (COALESCE(SUM(pd.existencia), 0) - COALESCE(SUM(pd.existencia_comprometida), 0)) AS stock_disponible,
               -- Ventas históricas en la ventana de tiempo
               COALESCE(ventas_hist.total_unidades_vendidas, 0) AS unidades_vendidas_periodo
           FROM productos p
           LEFT JOIN proveedores pr ON p.proveedor_habitual_id = pr.id
           LEFT JOIN producto_deposito pd ON p.id = pd.producto_id
           LEFT JOIN (
               SELECT 
                   vd.producto_id,
                   SUM(vd.cantidad) AS total_unidades_vendidas
               FROM ventas_detalles vd
               INNER JOIN ventas v ON vd.venta_id = v.id
               WHERE v.fecha_emision >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
                 AND v.estado != 'ANULADA'
                 AND v.tipo_documento IN ('FACTURA', 'NOTA_ENTREGA')
               GROUP BY vd.producto_id
           ) ventas_hist ON p.id = ventas_hist.producto_id
           WHERE p.estado = 1
       ";
       $params = ['dias' => $diasHistorico];
       if ($proveedorId) {
           $sql .= " AND p.proveedor_habitual_id = :prov";
           $params['prov'] = $proveedorId;
       }
       if ($categoriaId) {
           $sql .= " AND p.categoria_id = :cat";
           $params['cat'] = $categoriaId;
       }
       $sql .= " GROUP BY p.id ORDER BY p.descripcion ASC";
       $stmt = $db->prepare($sql);
       $stmt->execute($params);
       $productos = $stmt->fetchAll();
       $resultados = [];
       foreach ($productos as $row) {
           $disponible = (float)$row['stock_disponible'];
           $enTransito = (float)$row['stock_en_transito'];
           $stockNetoProyectado = $disponible + $enTransito;
           $vpd = (float)$row['unidades_vendidas_periodo'] / max(1, $diasHistorico);
           if ($row['modo_calculo_stock'] === 'ADAPTATIVO_HISTORICO') {
               $leadTime   = (int)$row['lead_time_dias'];
               $seguridad  = (int)$row['dias_seguridad_stock'];
               $cobertura  = (int)$row['dias_cobertura_maxima'];
               $minimoCalculado = ceil($vpd * ($leadTime + $seguridad));
               $maximoCalculado = ceil($vpd * ($leadTime + $seguridad + $cobertura));
           } else {
               $minimoCalculado = (float)$row['stock_minimo_manual'];
               $maximoCalculado = (float)$row['stock_maximo_manual'];
           }
           // Sugerencia: reponer hasta el stock máximo solo si el stock neto proyectado está por debajo o igual al mín
           $sugeridoComprar = 0.0;
           $requiereCompra = false;
           if ($stockNetoProyectado <= $minimoCalculado) {
               $requiereCompra = true;

               $sugeridoComprar = max(0, $maximoCalculado - $stockNetoProyectado);
           }
           $costoRef = (float)($row['costo_ultimo'] > 0 ? $row['costo_ultimo'] : $row['costo_promedio']);
           $resultados[] = [
               'producto_id'             => (int)$row['id'],
               'codigo'                  => $row['codigo'],
               'descripcion'             => $row['descripcion'],
               'proveedor_id'            => $row['proveedor_habitual_id'],
               'proveedor_nombre'        => $row['proveedor_nombre'] ?? 'Sin Proveedor Asignado',
               'modo_calculo'            => $row['modo_calculo_stock'],
               'venta_promedio_diaria'   => round($vpd, 2),
               'stock_disponible'        => $disponible,
               'stock_en_transito'       => $enTransito,
               'stock_neto_proyectado'   => $stockNetoProyectado,
               'stock_minimo_aplicado'   => $minimoCalculado,
               'stock_maximo_aplicado'   => $maximoCalculado,
               'requiere_compra'         => $requiereCompra,
               'cantidad_sugerida'       => $sugeridoComprar,
               'costo_unitario_estimado' => $costoRef,
               'inversion_estimada'      => round($sugeridoComprar * $costoRef, 2)
           ];
       }
       return $resultados;
   }
}