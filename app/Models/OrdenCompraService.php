<?php
namespace App\Models;

use App\Core\Database;
use Exception;

/**
 * OrdenCompraService — Gestión de Órdenes de Compra manuales
 */
class OrdenCompraService {

    /**
     * Crea una nueva Orden de Compra en estado PENDIENTE.
     */
    public static function crearOrden(array $cabecera, array $items): array {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            if (empty($cabecera['proveedor_id'])) throw new Exception("Se requiere el proveedor.");
            if (empty($items)) throw new Exception("La OC debe tener al menos un renglón.");

            // Generar número de OC
            $stmt = $db->query("SELECT COALESCE(MAX(id), 0) + 1 AS siguiente FROM compras WHERE tipo_documento = 'ORDEN_COMPRA'");
            $num = $stmt->fetch(\PDO::FETCH_ASSOC)['siguiente'] ?? 1;
            $numeroOC = 'OC-' . date('ym') . str_pad($num, 4, '0', STR_PAD_LEFT);

            // Calcular totales
            $subtotal = 0.0;
            $totalIva = 0.0;
            foreach ($items as $item) {
                $cant = (float)($item['cantidad'] ?? 0);
                $costo = (float)($item['costo_unitario'] ?? 0);
                $desc = (float)($item['porcentaje_descuento'] ?? 0);
                $iva  = (float)($item['porcentaje_iva'] ?? 16);
                $lineaNet = $cant * $costo * (1 - $desc / 100);
                $subtotal += $lineaNet;
                $totalIva += $lineaNet * ($iva / 100);
            }
            $total = $subtotal + $totalIva;

            // Insertar cabecera
            $subtotalR = round($subtotal, 2);
            $ivaR      = round($totalIva, 2);
            $totalR    = round($total, 2);

            $stmtCab = $db->prepare("
                INSERT INTO compras (
                    proveedor_id, deposito_id, tipo_documento, numero_factura,
                    fecha_emision, fecha_recepcion, fecha_vencimiento,
                    condicion_pago, moneda_id, tasa_cambio,
                    subtotal_neto, monto_exento, base_imponible,
                    monto_iva, retencion_iva_monto, retencion_islr_monto,
                    total_general, total_a_pagar, saldo_pendiente,
                    estado, nota, usuario_id, created_at
                ) VALUES (
                    :prov, :dep, 'ORDEN_COMPRA', :num_oc,
                    CURDATE(), CURDATE(), CURDATE(),
                    :cond_pago, 1, 1.000000,
                    :sub_neto, 0, :base_imp,
                    :monto_iva, 0, 0,
                    :total_gen, :total_pagar, :saldo,
                    'PENDIENTE', :nota, :uid, NOW()
                )
            ");
            $stmtCab->execute([
                'prov'        => (int)$cabecera['proveedor_id'],
                'dep'         => (int)($cabecera['deposito_id'] ?? 1),
                'num_oc'      => $numeroOC,
                'cond_pago'   => $cabecera['condicion_pago'] ?? 'CONTADO',
                'sub_neto'    => $subtotalR,
                'base_imp'    => $subtotalR,
                'monto_iva'   => $ivaR,
                'total_gen'   => $totalR,
                'total_pagar' => $totalR,
                'saldo'       => $totalR,
                'nota'        => $cabecera['nota'] ?? null,
                'uid'         => (int)($cabecera['usuario_id'] ?? 1),
            ]);
            $compraId = (int)$db->lastInsertId();

            // Insertar detalles
            $stmtDet = $db->prepare("
                INSERT INTO compras_detalles (
                    compra_id, producto_id, cantidad, costo_unitario,
                    porcentaje_descuento, porcentaje_iva, subtotal, total
                ) VALUES (
                    :cid, :pid, :cant, :costo, :desc, :pct_iva, :sub, :total
                )
            ");
            foreach ($items as $item) {
                $cant    = (float)($item['cantidad'] ?? 0);
                $costo   = (float)($item['costo_unitario'] ?? 0);
                $desc    = (float)($item['porcentaje_descuento'] ?? 0);
                $pctIva  = (float)($item['porcentaje_iva'] ?? 16);
                $sub     = $cant * $costo * (1 - $desc / 100);
                $totalDet = $sub * (1 + $pctIva / 100);
                $stmtDet->execute([
                    'cid'     => $compraId,
                    'pid'     => (int)$item['producto_id'],
                    'cant'    => $cant,
                    'costo'   => $costo,
                    'desc'    => $desc,
                    'pct_iva' => $pctIva,
                    'sub'     => round($sub, 4),
                    'total'   => round($totalDet, 4),
                ]);
            }

            $db->commit();
            return ['compra_id' => $compraId, 'numero_oc' => $numeroOC, 'total' => $total];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
