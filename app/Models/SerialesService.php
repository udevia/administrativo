<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Exception;
use PDO;

class SerialesService {
    /**
     * Registra los seriales al procesar una Compra o Recepción
     */
    public static function registrarEntradaSeriales(int $compraId, int $productoId, int $depositoId, array $seriales, float $costoUnitario): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO producto_seriales 
            (producto_id, deposito_id, numero_serial, estado, costo_unitario_compra, compra_id, fecha_ingreso)
            VALUES (:pid, :dep, :serial, 'DISPONIBLE', :costo, :cid, CURDATE())
        ");
        foreach ($seriales as $s) {
            $serialLimpio = trim((string)$s);
            if (empty($serialLimpio)) continue;
            try {
                $stmt->execute([
                    'pid'    => $productoId,
                    'dep'    => $depositoId,
                    'serial' => $serialLimpio,
                    'costo'  => $costoUnitario,
                    'cid'    => $compraId
                ]);
            } catch (Exception $e) {
                throw new Exception("El serial '{$serialLimpio}' ya existe registrado en el sistema para este producto.");
            }
        }
    }

    /**
     * Descarga y asienta los seriales vendidos en la Facturación POS
     */
    public static function despacharSerialesVenta(int $ventaId, int $ventaDetalleId, int $productoId, int $depositoId, array $serialesSeleccionados, int $clienteId): void {
        $db = Database::getConnection();
        // Obtener días de garantía del producto
        $stmtProd = $db->prepare("SELECT dias_garantia FROM productos WHERE id = :id");
        $stmtProd->execute(['id' => $productoId]);
        $diasGarantia = (int)$stmtProd->fetchColumn() ?: 90;

        $stmtCheck = $db->prepare("
            SELECT id, estado FROM producto_seriales 
            WHERE producto_id = :pid AND deposito_id = :dep AND numero_serial = :serial
            FOR UPDATE
        ");
        $stmtUpd = $db->prepare("
            UPDATE producto_seriales 
            SET estado = 'VENDIDO', venta_id = :vid, cliente_id = :cli, 
                fecha_venta = CURDATE(), 
                fecha_vencimiento_garantia = DATE_ADD(CURDATE(), INTERVAL :dias DAY)
            WHERE id = :id
        ");
        $stmtDet = $db->prepare("INSERT INTO ventas_detalles_seriales (venta_detalle_id, serial_id) VALUES (:vd, :sid)");

        foreach ($serialesSeleccionados as $serial) {
            $serialLimpio = trim((string)$serial);
            $stmtCheck->execute(['pid' => $productoId, 'dep' => $depositoId, 'serial' => $serialLimpio]);
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new Exception("El serial '{$serialLimpio}' no existe en el almacén seleccionado.");
            }
            if ($row['estado'] !== 'DISPONIBLE') {
                throw new Exception("El serial '{$serialLimpio}' no está disponible para la venta (Estado actual: {$row['estado']}).");
            }
            $stmtUpd->execute([
                'vid'  => $ventaId,
                'cli'  => $clienteId,
                'dias' => $diasGarantia,
                'id'   => $row['id']
            ]);
            $stmtDet->execute([
                'vd'  => $ventaDetalleId,
                'sid' => $row['id']
            ]);
        }
    }

    /**
     * Auditoría de Garantía: Busca un serial y devuelve su historial completo
     */
    public static function consultarHistorialSerial(string $numeroSerial): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                ps.*,
                p.codigo AS producto_codigo, p.descripcion AS producto_descripcion,
                c.razon_social AS cliente_nombre, c.documento_fiscal AS cliente_rif, c.telefono AS cliente_telefono,
                v.numero_documento AS factura_numero, v.fecha_emision AS fecha_factura,
                pr.razon_social AS proveedor_nombre,
                DATEDIFF(ps.fecha_vencimiento_garantia, CURDATE()) AS dias_garantia_restantes
            FROM producto_seriales ps
            INNER JOIN productos p ON ps.producto_id = p.id
            LEFT JOIN clientes c ON ps.cliente_id = c.id
            LEFT JOIN ventas v ON ps.venta_id = v.id
            LEFT JOIN compras com ON ps.compra_id = com.id
            LEFT JOIN proveedores pr ON com.proveedor_id = pr.id
            WHERE ps.numero_serial = :s
            LIMIT 1
        ");
        $stmt->execute(['s' => trim($numeroSerial)]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }
}