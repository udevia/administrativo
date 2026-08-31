<?php
namespace App\Models;
use App\Core\Database;
use PDO;
class ReportesFiscalesService {
   /**
    * Genera el Libro de Ventas para un período fiscal (Mes / Año)
    */
   public static function getLibroVentas(int $mes, int $ano): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               v.id,
               v.fecha_emision,
               v.tipo_documento,
               v.numero_documento,
               v.control_fiscal,
               v.hka_cufe,
               c.razon_social AS cliente_nombre,
               c.documento_fiscal AS cliente_rif,
               c.tipo_contribuyente,
               v.total_general,
               v.monto_exento,
               v.base_imponible,
               v.monto_iva,
               v.monto_igtf,
               COALESCE(SUM(cxc.monto), 0) AS retencion_iva_retenida
           FROM ventas v
           INNER JOIN clientes c ON v.cliente_id = c.id
           LEFT JOIN cuentas_por_cobrar cxc ON v.id = cxc.venta_id AND cxc.tipo_transaccion = 'RETENCION_IVA'
           WHERE MONTH(v.fecha_emision) = :mes 
             AND YEAR(v.fecha_emision) = :ano
             AND v.tipo_documento IN ('FACTURA', 'NOTA_CREDITO', 'NOTA_DEBITO')
             AND v.estado != 'ANULADA'
           GROUP BY v.id
           ORDER BY v.fecha_emision ASC, v.numero_documento ASC

       ");
       $stmt->execute(['mes' => $mes, 'ano' => $ano]);
       return $stmt->fetchAll();
   }
   /**
    * Genera el Libro de Compras para un período fiscal
    */
   public static function getLibroCompras(int $mes, int $ano): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               co.id,
               co.fecha_emision,
               co.fecha_recepcion,
               co.tipo_documento,
               co.numero_factura,
               co.numero_control,
               p.razon_social AS proveedor_nombre,
               p.documento_fiscal AS proveedor_rif,
               co.total_general,
               co.monto_exento,
               co.base_imponible,
               co.monto_iva,
               co.retencion_iva_monto,
               co.retencion_islr_monto
           FROM compras co
           INNER JOIN proveedores p ON co.proveedor_id = p.id
           WHERE MONTH(co.fecha_recepcion) = :mes 
             AND YEAR(co.fecha_recepcion) = :ano
             AND co.estado != 'ANULADA'
           ORDER BY co.fecha_recepcion ASC, co.numero_factura ASC
       ");
       $stmt->execute(['mes' => $mes, 'ano' => $ano]);
       return $stmt->fetchAll();
   }
   /**
    * Kardex Valorizado por Producto y Depósito
    */
    public static function getKardexProducto(int $productoId, ?int $depositoId = null, ?string $fechaDesde = null, ?string $fechaHasta = null): array {
        $db = Database::getConnection();
       $sql = "
           SELECT 
               k.*,
               d.descripcion AS deposito_nombre,
               u.nombre AS usuario_nombre,
               p.descripcion AS producto_nombre,
               p.codigo AS producto_codigo
           FROM kardex_inventario k
           INNER JOIN productos p ON k.producto_id = p.id
           INNER JOIN depositos d ON k.deposito_id = d.id
           INNER JOIN usuarios u ON k.usuario_id = u.id
           WHERE k.producto_id = :p
       ";
       $params = ['p' => $productoId];
       if ($depositoId) {
           $sql .= " AND k.deposito_id = :d";
           $params['d'] = $depositoId;
       }
       if ($fechaDesde && $fechaHasta) {
           $sql .= " AND DATE(k.fecha_hora) BETWEEN :f1 AND :f2";
           $params['f1'] = $fechaDesde;
           $params['f2'] = $fechaHasta;
       }
       $sql .= " ORDER BY k.id ASC";
       $stmt = $db->prepare($sql);
       $stmt->execute($params);
       return $stmt->fetchAll();
   }
}
