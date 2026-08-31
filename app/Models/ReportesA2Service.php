<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class ReportesA2Service extends ReportesMiService {
   // ==========================================
   // 1. VENTAS Y COMERCIALIZACIÓN
   // ==========================================
   public static function ventasPorCliente(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               c.codigo, c.razon_social, c.documento_fiscal,
               COUNT(v.id) AS total_documentos,
               COALESCE(SUM(v.subtotal_neto), 0) AS subtotal,
               COALESCE(SUM(v.monto_iva), 0) AS iva,
               COALESCE(SUM(v.total_general), 0) AS total
           FROM clientes c
           INNER JOIN ventas v ON c.id = v.cliente_id
           WHERE v.fecha_emision BETWEEN :d1 AND :d2 AND v.estado != 'ANULADA'
           GROUP BY c.id
           ORDER BY total DESC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   public static function ventasPorArticulo(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               p.codigo, p.descripcion,
               SUM(vd.cantidad) AS unidades_vendidas,
               SUM(vd.subtotal) AS total_neto,
               SUM(vd.total) AS total_bruto
           FROM productos p
           INNER JOIN ventas_detalles vd ON p.id = vd.producto_id
           INNER JOIN ventas v ON vd.venta_id = v.id
           WHERE v.fecha_emision BETWEEN :d1 AND :d2 AND v.estado != 'ANULADA'
           GROUP BY p.id
           ORDER BY unidades_vendidas DESC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   public static function ventasPorZona(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               z.codigo, z.descripcion AS zona,
               COUNT(DISTINCT c.id) AS total_clientes,
               COUNT(v.id) AS total_facturas,
               COALESCE(SUM(v.total_general), 0) AS total_vendido
           FROM zonas z
           INNER JOIN clientes c ON z.id = c.zona_id
           INNER JOIN ventas v ON c.id = v.cliente_id
           WHERE v.fecha_emision BETWEEN :d1 AND :d2 AND v.estado != 'ANULADA'
           GROUP BY z.id
           ORDER BY total_vendido DESC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   public static function documentosAnulados(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               v.fecha_emision, v.tipo_documento, v.numero_documento,
               c.razon_social AS cliente, v.total_general, v.nota, u.nombre AS usuario
           FROM ventas v
           INNER JOIN clientes c ON v.cliente_id = c.id

           INNER JOIN usuarios u ON v.usuario_id = u.id
           WHERE v.estado = 'ANULADA' AND v.fecha_emision BETWEEN :d1 AND :d2
           ORDER BY v.fecha_emision DESC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   // ==========================================
   // 2. COMPRAS Y PROVEEDORES
   // ==========================================
   public static function comprasPorProveedor(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               pr.codigo, pr.razon_social, pr.documento_fiscal,
               COUNT(c.id) AS total_compras,
               COALESCE(SUM(c.base_imponible), 0) AS base_imponible,
               COALESCE(SUM(c.monto_iva), 0) AS monto_iva,
               COALESCE(SUM(c.retencion_iva_monto), 0) AS ret_iva,
               COALESCE(SUM(c.retencion_islr_monto), 0) AS ret_islr,
               COALESCE(SUM(c.total_general), 0) AS total
           FROM proveedores pr
           INNER JOIN compras c ON pr.id = c.proveedor_id
           WHERE c.fecha_recepcion BETWEEN :d1 AND :d2 AND c.estado != 'ANULADA'
           GROUP BY pr.id
           ORDER BY total DESC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   public static function ordenesCompraPendientes(): array {
       $db = Database::getConnection();
       return $db->query("
           SELECT 
               c.numero_factura AS numero_orden, c.fecha_emision, c.fecha_recepcion AS entrega_esperada,
               pr.razon_social AS proveedor, d.descripcion AS deposito_destino,
               c.total_general, c.estado_flujo
           FROM compras c
           INNER JOIN proveedores pr ON c.proveedor_id = pr.id
           INNER JOIN depositos d ON c.deposito_id = d.id
           WHERE c.tipo_documento = 'ORDEN_COMPRA' AND c.estado_flujo IN ('PENDIENTE', 'RECEPCION_PARCIAL')
           ORDER BY c.fecha_emision ASC
       ")->fetchAll();
   }
   public static function retencionesAcumuladas(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               c.fecha_recepcion, c.numero_factura, c.numero_control,
               pr.razon_social AS proveedor, pr.documento_fiscal,
               c.base_imponible, c.monto_iva, c.retencion_iva_monto, c.retencion_islr_monto
           FROM compras c
           INNER JOIN proveedores pr ON c.proveedor_id = pr.id
           WHERE (c.retencion_iva_monto > 0 OR c.retencion_islr_monto > 0)
             AND c.fecha_recepcion BETWEEN :d1 AND :d2 AND c.estado != 'ANULADA'
           ORDER BY c.fecha_recepcion ASC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   // ==========================================
   // 3. INVENTARIO Y ALMACÉN
   // ==========================================
   public static function existenciasPorDeposito(): array {
       $db = Database::getConnection();
       return $db->query("
           SELECT 
               d.codigo AS deposito_codigo, d.descripcion AS deposito,

               p.codigo AS producto_codigo, p.descripcion AS producto,
               pd.existencia, pd.existencia_comprometida, pd.existencia_por_llegar,
               (pd.existencia - pd.existencia_comprometida) AS disponible,
               p.costo_promedio,
               (pd.existencia * p.costo_promedio) AS valor_total
           FROM producto_deposito pd
           INNER JOIN productos p ON pd.producto_id = p.id
           INNER JOIN depositos d ON pd.deposito_id = d.id
           WHERE pd.existencia > 0
           ORDER BY d.id, p.descripcion
       ")->fetchAll();
   }
   public static function inventarioValorizado(): array {
       $db = Database::getConnection();
       return $db->query("
           SELECT 
               p.codigo, p.descripcion,
               COALESCE(SUM(pd.existencia), 0) AS stock_total,
               p.costo_ultimo, p.costo_promedio, p.costo_reposicion,
               (COALESCE(SUM(pd.existencia), 0) * p.costo_promedio) AS valor_promedio_total,
               (COALESCE(SUM(pd.existencia), 0) * p.costo_ultimo) AS valor_ultimo_costo_total
           FROM productos p
           LEFT JOIN producto_deposito pd ON p.id = pd.producto_id
           WHERE p.estado = 1
           GROUP BY p.id
           HAVING stock_total > 0
           ORDER BY valor_promedio_total DESC
       ")->fetchAll();
   }
   public static function lotesProximosVencer(int $dias = 60): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               p.codigo, p.descripcion, d.descripcion AS deposito,
               pl.numero_lote, pl.fecha_fabricacion, pl.fecha_vencimiento,
               pl.existencia, DATEDIFF(pl.fecha_vencimiento, CURDATE()) AS dias_restantes
           FROM producto_lotes pl
           INNER JOIN productos p ON pl.producto_id = p.id
           INNER JOIN depositos d ON pl.deposito_id = d.id
           WHERE pl.existencia > 0 
             AND pl.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
           ORDER BY pl.fecha_vencimiento ASC
       ");
       $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
       $stmt->execute();
       return $stmt->fetchAll();
   }
   // ==========================================
   // 4. CUENTAS POR COBRAR (CxC)
   // ==========================================
   public static function estadoCuentaCliente(int $clienteId): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               v.fecha_emision, v.tipo_documento, v.numero_documento, v.fecha_vencimiento,
               v.total_general, v.saldo_pendiente,
               DATEDIFF(CURDATE(), v.fecha_vencimiento) AS dias_vencido,
               v.estado
           FROM ventas v
           WHERE v.cliente_id = :cid AND v.saldo_pendiente > 0 AND v.estado != 'ANULADA'
           ORDER BY v.fecha_emision ASC
       ");
       $stmt->execute(['cid' => $clienteId]);
       return $stmt->fetchAll();
   }
   public static function relacionCobranzas(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 

               rc.numero_recibo, rc.fecha, c.razon_social AS cliente,
               fp.forma_pago, fp.referencia, fp.monto,
               cb.nombre_banco, u.nombre AS cobrador
           FROM recibos_cobranza rc
           INNER JOIN clientes c ON rc.cliente_id = c.id
           INNER JOIN recibos_cobranza_formas_pago fp ON rc.id = fp.recibo_id
           INNER JOIN cuentas_bancarias cb ON fp.cuenta_id = cb.id
           INNER JOIN usuarios u ON rc.usuario_id = u.id
           WHERE rc.fecha BETWEEN :d1 AND :d2
           ORDER BY rc.fecha DESC, rc.numero_recibo DESC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   // ==========================================
   // 5. CUENTAS POR PAGAR (CxP)
   // ==========================================
   public static function estadoCuentaProveedor(int $proveedorId): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               c.fecha_emision, c.tipo_documento, c.numero_factura, c.fecha_vencimiento,
               c.total_a_pagar, c.saldo_pendiente,
               DATEDIFF(CURDATE(), c.fecha_vencimiento) AS dias_vencido,
               c.estado
           FROM compras c
           WHERE c.proveedor_id = :pid AND c.saldo_pendiente > 0 AND c.estado != 'ANULADA'
           ORDER BY c.fecha_emision ASC
       ");
       $stmt->execute(['pid' => $proveedorId]);
       return $stmt->fetchAll();
   }
    public static function antiguedadSaldosCxP(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT 
                pr.id AS proveedor_id, pr.codigo, pr.razon_social, pr.documento_fiscal, pr.saldo_actual,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_vencimiento) <= 0 THEN c.saldo_pendiente ELSE 0 END), 0) AS por_vencer,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_vencimiento) BETWEEN 1 AND 15 THEN c.saldo_pendiente ELSE 0 END), 0) AS vencido_1_15,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_vencimiento) BETWEEN 16 AND 30 THEN c.saldo_pendiente ELSE 0 END), 0) AS vencido_16_30,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_vencimiento) > 30 THEN c.saldo_pendiente ELSE 0 END), 0) AS vencido_mas_30
            FROM proveedores pr
            LEFT JOIN compras c ON pr.id = c.proveedor_id AND c.saldo_pendiente > 0 AND c.estado != 'ANULADA'
            WHERE pr.saldo_actual > 0
            GROUP BY pr.id
            ORDER BY pr.saldo_actual DESC
        ")->fetchAll();
    }
   // ==========================================
   // 6. BANCOS, CAJA Y TESORERÍA
   // ==========================================
   public static function estadoCuentaBancos(int $cuentaId, string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               mb.fecha, mb.tipo_movimiento, mb.numero_referencia,
               mb.beneficiario_concepto, mb.monto, mb.tasa_cambio,
               mb.conciliado, u.nombre AS usuario
           FROM movimientos_bancarios mb
           INNER JOIN usuarios u ON mb.usuario_id = u.id
           WHERE mb.cuenta_id = :cid AND mb.fecha BETWEEN :d1 AND :d2
           ORDER BY mb.fecha ASC, mb.id ASC
       ");
       $stmt->execute(['cid' => $cuentaId, 'd1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   // ==========================================
   // 7. FISCAL Y AUDITORÍA

   // ==========================================
   public static function auditoriaCorrelativos(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               v.fecha_emision, v.tipo_documento, v.numero_documento, v.control_fiscal,
               v.hka_cufe, v.hka_status, v.total_general, v.estado,
               c.razon_social AS cliente, u.nombre AS usuario
           FROM ventas v
           INNER JOIN clientes c ON v.cliente_id = c.id
           INNER JOIN usuarios u ON v.usuario_id = u.id
           WHERE v.tipo_documento IN ('FACTURA', 'NOTA_DEBITO', 'DEVOLUCION_VENTA')
             AND v.fecha_emision BETWEEN :d1 AND :d2
           ORDER BY v.id ASC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
}