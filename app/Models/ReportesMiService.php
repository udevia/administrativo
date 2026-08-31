<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class ReportesMiService {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ventasPorArticulo(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                p.codigo, p.descripcion,
                SUM(d.cantidad) AS total_unidades,
                SUM(d.total) AS total_ventas_usd
            FROM productos p
            INNER JOIN ventas_detalles d ON p.id = d.producto_id
            INNER JOIN ventas v ON d.venta_id = v.id
            WHERE v.fecha_emision BETWEEN :d1 AND :d2 AND v.estado != 'ANULADA'
            GROUP BY p.id
            ORDER BY total_ventas_usd DESC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ventasPorZona(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                z.descripcion AS zona,
                COUNT(v.id) AS total_facturas,
                COALESCE(SUM(v.total_general), 0) AS total_ventas
            FROM zonas z
            INNER JOIN clientes c ON z.id = c.zona_id
            INNER JOIN ventas v ON c.id = v.cliente_id
            WHERE v.fecha_emision BETWEEN :d1 AND :d2 AND v.estado != 'ANULADA'
            GROUP BY z.id
            ORDER BY total_ventas DESC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function documentosAnulados(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT v.tipo_documento, v.numero_documento, v.fecha_emision, c.razon_social, v.total_general, v.created_at
            FROM ventas v
            INNER JOIN clientes c ON v.cliente_id = c.id
            WHERE v.estado = 'ANULADA' AND v.fecha_emision BETWEEN :d1 AND :d2
            ORDER BY v.created_at DESC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // 2. COMPRAS Y CUENTAS POR PAGAR (CxP)
    // ==========================================
    public static function comprasPorProveedor(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                pr.codigo, pr.razon_social, pr.documento_fiscal,
                COUNT(c.id) AS total_facturas,
                COALESCE(SUM(c.total_general), 0) AS total_compras
            FROM proveedores pr
            INNER JOIN compras c ON pr.id = c.proveedor_id
            WHERE c.fecha_emision BETWEEN :d1 AND :d2 AND c.estado != 'ANULADA'
            GROUP BY pr.id
            ORDER BY total_compras DESC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ordenesCompraPendientes(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT oc.id, oc.numero_orden, pr.razon_social AS proveedor, oc.fecha_emision, oc.total_general AS monto_total_usd, oc.estado
            FROM ordenes_compra oc
            INNER JOIN proveedores pr ON oc.proveedor_id = pr.id
            WHERE oc.estado IN ('APROBADA', 'RECEPCION_PARCIAL', 'PENDIENTE')
            ORDER BY oc.fecha_emision ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function retencionesAcumuladas(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                c.fecha_emision, c.numero_factura, pr.razon_social, pr.documento_fiscal,
                c.total_general, c.monto_iva,
                (c.monto_iva * 0.75) AS retencion_iva_estimada
            FROM compras c
            INNER JOIN proveedores pr ON c.proveedor_id = pr.id
            WHERE c.fecha_emision BETWEEN :d1 AND :d2 AND c.estado != 'ANULADA'
            ORDER BY c.fecha_emision DESC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // 3. INVENTARIOS Y VALORIZACIÓN
    // ==========================================
    public static function existenciasPorDeposito(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT 
                d.descripcion AS deposito, p.codigo, p.descripcion AS producto,
                ie.existencia, p.costo_ultimo, (ie.existencia * p.costo_ultimo) AS valor_total_usd
            FROM inventario_existencias ie
            INNER JOIN depositos d ON ie.deposito_id = d.id
            INNER JOIN productos p ON ie.producto_id = p.id
            ORDER BY d.descripcion, p.descripcion
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function inventarioValorizado(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT 
                c.descripcion AS categoria,
                COUNT(p.id) AS total_items,
                SUM(COALESCE(ie.existencia, 0)) AS total_unidades,
                SUM(COALESCE(ie.existencia, 0) * p.costo_ultimo) AS valor_al_costo_usd,
                SUM(COALESCE(ie.existencia, 0) * p.precio_a) AS valor_al_pvp_usd
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN inventario_existencias ie ON p.id = ie.producto_id
            WHERE p.estado = 1
            GROUP BY c.id
            ORDER BY valor_al_costo_usd DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function lotesProximosVencer(int $dias = 60): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                p.codigo, p.descripcion, l.numero_lote, l.fecha_vencimiento, l.existencia AS cantidad_disponible,
                DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias_para_vencer
            FROM producto_lotes l
            INNER JOIN productos p ON l.producto_id = p.id
            WHERE l.existencia > 0 AND l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
            ORDER BY l.fecha_vencimiento ASC
        ");
        $stmt->execute(['dias' => $dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // 4. CUENTAS POR COBRAR Y ESTADOS DE CUENTA
    // ==========================================
    public static function estadoCuentaCliente(int $clienteId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT v.tipo_documento, v.numero_documento, v.fecha_emision, v.fecha_vencimiento, v.total_general, v.saldo_pendiente
            FROM ventas v
            WHERE v.cliente_id = :id AND v.saldo_pendiente > 0 AND v.estado != 'ANULADA'
            ORDER BY v.fecha_emision ASC
        ");
        $stmt->execute(['id' => $clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function relacionCobranzas(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT rc.numero_recibo, rc.fecha, c.razon_social, rc.monto_total, rc.tasa_cambio, COALESCE(u.nombre, u.usuario, 'Cajero') AS cobrador
            FROM recibos_cobranza rc
            INNER JOIN clientes c ON rc.cliente_id = c.id
            LEFT JOIN usuarios u ON rc.usuario_id = u.id
            WHERE rc.fecha BETWEEN :d1 AND :d2
            ORDER BY rc.fecha DESC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function antiguedadSaldosCxP(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT 
                pr.codigo, pr.razon_social,
                SUM(c.saldo_pendiente) AS total_deuda,
                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_vencimiento) <= 0 THEN c.saldo_pendiente ELSE 0 END) AS corriente,
                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_vencimiento) BETWEEN 1 AND 30 THEN c.saldo_pendiente ELSE 0 END) AS de_1_a_30,
                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_vencimiento) BETWEEN 31 AND 60 THEN c.saldo_pendiente ELSE 0 END) AS de_31_a_60,
                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_vencimiento) > 60 THEN c.saldo_pendiente ELSE 0 END) AS mas_60
            FROM compras c
            INNER JOIN proveedores pr ON c.proveedor_id = pr.id
            WHERE c.saldo_pendiente > 0 AND c.estado != 'ANULADA'
            GROUP BY pr.id
            ORDER BY total_deuda DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function estadoCuentaProveedor(int $proveedorId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.numero_factura, c.fecha_emision, c.fecha_vencimiento, c.total_general, c.saldo_pendiente
            FROM compras c
            WHERE c.proveedor_id = :id AND c.saldo_pendiente > 0 AND c.estado != 'ANULADA'
            ORDER BY c.fecha_emision ASC
        ");
        $stmt->execute(['id' => $proveedorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // 5. BANCOS Y AUDITORÍA FISCAL
    // ==========================================
    public static function estadoCuentaBancos(int $cuentaId, string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT mb.fecha AS fecha_movimiento, mb.tipo_movimiento, mb.numero_referencia, mb.monto, mb.conciliado, mb.beneficiario_concepto AS concepto
            FROM movimientos_bancarios mb
            WHERE mb.cuenta_id = :cta AND mb.fecha BETWEEN :d1 AND :d2
            ORDER BY mb.fecha ASC, mb.id ASC
        ");
        $stmt->execute(['cta' => $cuentaId, 'd1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function auditoriaCorrelativos(string $desde, string $hasta): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT tipo_documento, numero_documento, fecha_emision, total_general, estado
            FROM ventas
            WHERE fecha_emision BETWEEN :d1 AND :d2
            ORDER BY tipo_documento, id ASC
        ");
        $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
