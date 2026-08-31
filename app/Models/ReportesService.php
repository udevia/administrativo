<?php
namespace App\Models;

use App\Core\Database;
use Exception;
use PDO;

class ReportesService {
    
    public static function generarReporte(string $tipo, string $desde, string $hasta): array {
        $db = Database::getConnection();
        
        switch ($tipo) {
            case 'ventas_resumen':
                $stmt = $db->prepare("
                    SELECT 
                        COUNT(id) as total_transacciones,
                        SUM(total_general) as monto_total,
                        SUM(saldo_pendiente) as saldo_por_cobrar
                    FROM ventas
                    WHERE DATE(fecha_emision) BETWEEN :desde AND :hasta
                      AND estado NOT IN ('ANULADA', 'PEDIDO', 'PRESUPUESTO')
                ");
                $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            case 'ventas_diarias':
                $stmt = $db->prepare("
                    SELECT DATE(fecha_emision) as fecha, SUM(total_general) as total
                    FROM ventas
                    WHERE DATE(fecha_emision) BETWEEN :desde AND :hasta
                      AND estado NOT IN ('ANULADA', 'PEDIDO', 'PRESUPUESTO')
                    GROUP BY DATE(fecha_emision)
                    ORDER BY fecha ASC
                ");
                $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'top_productos':
                $stmt = $db->prepare("
                    SELECT p.codigo, p.descripcion, SUM(vd.cantidad) as cantidad_vendida, SUM(vd.subtotal) as monto_generado
                    FROM ventas_detalles vd
                    JOIN ventas v ON vd.venta_id = v.id
                    JOIN productos p ON vd.producto_id = p.id
                    WHERE DATE(v.fecha_emision) BETWEEN :desde AND :hasta
                      AND v.estado NOT IN ('ANULADA', 'PEDIDO', 'PRESUPUESTO')
                    GROUP BY p.id
                    ORDER BY monto_generado DESC
                    LIMIT 10
                ");
                $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'compras_resumen':
                $stmt = $db->prepare("
                    SELECT 
                        COUNT(id) as total_compras,
                        SUM(monto_total) as monto_total
                    FROM compras
                    WHERE DATE(fecha_emision) BETWEEN :desde AND :hasta
                      AND estado NOT IN ('ANULADA', 'PENDIENTE')
                ");
                $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            case 'inventario_valorizacion':
                $stmt = $db->query("
                    SELECT SUM(stock_actual * costo_usd) as valor_total,
                           SUM(stock_actual * precio_base_usd) as pvp_total
                    FROM productos
                    WHERE tipo = 'FISICO' AND stock_actual > 0
                ");
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            default:
                throw new Exception("Tipo de reporte no soportado: {$tipo}");
        }
    }
}
