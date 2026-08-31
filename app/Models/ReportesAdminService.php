<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class ReportesAdminService {
    /**
     * 1. Cuentas por Cobrar (CxC) con Antigüedad de Saldos (Corriente, 15, 30, 45, +60 días)
     */
    public static function getAntiguedadSaldosCxC(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT 
                c.id AS cliente_id,
                c.codigo,
                c.razon_social,
                c.documento_fiscal,
                c.telefono,
                c.limite_credito,
                c.saldo_actual,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_vencimiento) <= 0 THEN v.saldo_pendiente ELSE 0 END), 0) AS corriente,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_vencimiento) BETWEEN 1 AND 15 THEN v.saldo_pendiente ELSE 0 END), 0) AS de_1_a_15,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_vencimiento) BETWEEN 16 AND 30 THEN v.saldo_pendiente ELSE 0 END), 0) AS de_16_a_30,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_vencimiento) BETWEEN 31 AND 60 THEN v.saldo_pendiente ELSE 0 END), 0) AS de_31_a_60,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_vencimiento) > 60 THEN v.saldo_pendiente ELSE 0 END), 0) AS mas_60
            FROM clientes c
            LEFT JOIN ventas v ON c.id = v.cliente_id AND v.saldo_pendiente > 0 AND v.estado != 'ANULADA'
            WHERE c.saldo_actual > 0
            GROUP BY c.id
            ORDER BY c.saldo_actual DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 2. Reporte de Ventas por Vendedor y Comisiones
     */
    public static function getVentasPorVendedor(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                v.id AS vendedor_id,
                v.nombre AS vendedor_nombre,
                v.codigo AS cedula_rif,
                COUNT(vt.id) AS total_facturas,
                COALESCE(SUM(vt.subtotal_neto), 0) AS total_neto_vendido,
                COALESCE(SUM(vt.total_general), 0) AS total_bruto_vendido,
                0.00 AS total_comisiones_generadas
            FROM vendedores v
            LEFT JOIN ventas vt ON v.id = vt.vendedor_id AND vt.fecha_emision BETWEEN :d1 AND :d2 AND vt.estado != 'ANULADA'
            GROUP BY v.id
            ORDER BY total_neto_vendido DESC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 3. Reporte de Stock Crítico y Puntos de Reorden
     */
    public static function getProductosBajoMinimo(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT 
                p.id, p.codigo, p.descripcion, p.stock_minimo, p.stock_maximo,
                d.descripcion AS deposito,
                pd.existencia AS stock_actual,
                pd.existencia_comprometida,
                (pd.existencia - pd.existencia_comprometida) AS stock_disponible,
                (p.stock_minimo - (pd.existencia - pd.existencia_comprometida)) AS cantidad_a_comprar
            FROM productos p
            INNER JOIN producto_deposito pd ON p.id = pd.producto_id
            INNER JOIN depositos d ON pd.deposito_id = d.id
            WHERE p.stock_minimo > 0 
              AND (pd.existencia - pd.existencia_comprometida) <= p.stock_minimo
              AND p.estado = 1
            ORDER BY stock_disponible ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 4. Rentabilidad y Utilidad Bruta por Producto
     */
    public static function getRentabilidadPorProducto(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                p.codigo,
                p.descripcion,
                SUM(vd.cantidad) AS unidades_vendidas,
                SUM(vd.subtotal) AS ingreso_total_neto,
                SUM(vd.cantidad * vd.costo_unitario_historico) AS costo_total_historico,
                (SUM(vd.subtotal) - SUM(vd.cantidad * vd.costo_unitario_historico)) AS utilidad_bruta,
                CASE 
                    WHEN SUM(vd.subtotal) > 0 THEN 
                        ROUND(((SUM(vd.subtotal) - SUM(vd.cantidad * vd.costo_unitario_historico)) / SUM(vd.subtotal)) * 100, 2)
                    ELSE 0 
                END AS margen_porcentual
            FROM ventas_detalles vd
            INNER JOIN ventas v ON vd.venta_id = v.id
            INNER JOIN productos p ON vd.producto_id = p.id
            WHERE v.fecha_emision BETWEEN :d1 AND :d2 AND v.estado != 'ANULADA'
            GROUP BY p.id
            ORDER BY utilidad_bruta DESC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}