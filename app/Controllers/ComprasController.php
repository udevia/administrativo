<?php
namespace App\Controllers;
use App\Models\ComprasService;
use App\Models\OrdenCompraService;
use App\Core\Database;
use App\Core\PdfEngine;
use Exception;

class ComprasController {

    public function procesar(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['cabecera']) || empty($input['items'])) {
            http_response_code(422);
            echo json_encode(["status" => "error", "message" => "Datos de compra incompletos."]);
            return;
        }
        try {
            $resultado = ComprasService::procesarCompra($input['cabecera'], $input['items']);
            header('Content-Type: application/json');
            echo json_encode([
                "status"  => "success",
                "message" => "Compra registrada e inventario actualizado con éxito.",
                "data"    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    public function procesarFactura(): void { $this->procesar(); }
    public function aprovisionar(): void { $this->procesar(); }

    /**
     * GET /api/compras/historial?desde=&hasta=&proveedor_id=
     */
    public function historial(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $desde = $_GET['desde'] ?? date('Y-m-01');
            $hasta = $_GET['hasta'] ?? date('Y-m-d');
            $provId = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : null;

            $where = ["c.fecha_emision BETWEEN :d1 AND :d2"];
            $params = ['d1' => $desde, 'd2' => $hasta];
            if ($provId) {
                $where[] = "c.proveedor_id = :pid";
                $params['pid'] = $provId;
            }
            $whereStr = implode(' AND ', $where);

            $stmt = $db->prepare("
                SELECT c.id, c.tipo_documento, c.numero_factura, c.fecha_emision,
                       c.estado, c.condicion_pago,
                       p.razon_social AS proveedor_nombre, p.documento_fiscal,
                       c.subtotal_neto, c.monto_iva, c.total_general, c.saldo_pendiente
                FROM compras c
                INNER JOIN proveedores p ON c.proveedor_id = p.id
                WHERE {$whereStr}
                ORDER BY c.id DESC
                LIMIT 500
            ");
            $stmt->execute($params);
            echo json_encode(["status" => "success", "data" => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    /**
     * GET /api/compras/ordenes — Lista Órdenes de Compra
     */
    public function listarOrdenes(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT c.id, c.tipo_documento, c.numero_factura, c.fecha_emision,
                       c.estado, c.condicion_pago, c.total_general,
                       p.razon_social AS proveedor_nombre
                FROM compras c
                INNER JOIN proveedores p ON c.proveedor_id = p.id
                WHERE c.tipo_documento = 'ORDEN_COMPRA'
                ORDER BY c.id DESC
                LIMIT 200
            ");
            echo json_encode(["status" => "success", "data" => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    /**
     * GET /api/compras/ordenes/pendientes
     */
    public function ordenesPendientes(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT c.id, c.numero_factura, c.fecha_emision, c.total_general,
                       p.razon_social AS proveedor_nombre, p.id AS proveedor_id,
                       d.descripcion AS deposito_nombre, c.deposito_id
                FROM compras c
                INNER JOIN proveedores p ON c.proveedor_id = p.id
                LEFT JOIN depositos d ON c.deposito_id = d.id
                WHERE c.tipo_documento = 'ORDEN_COMPRA' AND c.estado = 'PENDIENTE'
                ORDER BY c.fecha_emision ASC
            ");
            $ordenes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Para cada OC, cargar sus items
            $stmtItems = $db->prepare("
                SELECT cd.*, p.codigo, p.descripcion, p.maneja_seriales
                FROM compras_detalles cd
                INNER JOIN productos p ON cd.producto_id = p.id
                WHERE cd.compra_id = :id
            ");
            foreach ($ordenes as &$oc) {
                $stmtItems->execute(['id' => $oc['id']]);
                $oc['items'] = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);
            }

            echo json_encode(["status" => "success", "data" => $ordenes]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    /**
     * POST /api/compras/orden — Crear Orden de Compra
     */
    public function crearOrden(): void {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['cabecera']) || empty($input['items'])) {
            http_response_code(422);
            echo json_encode(["status" => "error", "message" => "Datos de OC incompletos."]);
            return;
        }
        try {
            $resultado = OrdenCompraService::crearOrden($input['cabecera'], $input['items']);
            echo json_encode([
                "status"  => "success",
                "message" => "Orden de Compra emitida exitosamente.",
                "data"    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    /**
     * POST /api/compras/recepcion — Recibir mercancía vinculada a una OC
     */
    public function procesarRecepcion(): void {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['orden_id']) || empty($input['items'])) {
            http_response_code(422);
            echo json_encode(["status" => "error", "message" => "Datos de recepción incompletos."]);
            return;
        }
        try {
            $db = Database::getConnection();
            // Obtener datos de la OC original
            $stmtOC = $db->prepare("SELECT * FROM compras WHERE id = :id AND tipo_documento = 'ORDEN_COMPRA'");
            $stmtOC->execute(['id' => $input['orden_id']]);
            $oc = $stmtOC->fetch(\PDO::FETCH_ASSOC);
            if (!$oc) throw new Exception("La Orden de Compra no existe o ya fue procesada.");

            // Procesar como factura de compra
            $cabecera = [
                'proveedor_id'       => $oc['proveedor_id'],
                'deposito_id'        => $input['deposito_id'] ?? $oc['deposito_id'],
                'numero_factura'     => $input['numero_factura_proveedor'] ?? 'RCP-' . date('ymd') . '-' . $oc['id'],
                'tipo_documento'     => 'FACTURA',
                'condicion_pago'     => $oc['condicion_pago'] ?? 'CONTADO',
                'orden_compra_id'    => $oc['id'],
            ];

            $resultado = ComprasService::procesarCompra($cabecera, $input['items']);

            // Marcar OC como RECIBIDA
            $db->prepare("UPDATE compras SET estado = 'RECIBIDA' WHERE id = :id")->execute(['id' => $oc['id']]);

            echo json_encode([
                "status"  => "success",
                "message" => "Recepción procesada. Inventario actualizado.",
                "data"    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    /**
     * GET /api/compras/{id}/documento — Impresión HTML
     */
    public function imprimirDocumento(int $id): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT c.*, p.razon_social, p.documento_fiscal, p.direccion_fiscal
                FROM compras c
                INNER JOIN proveedores p ON c.proveedor_id = p.id
                WHERE c.id = :id
            ");
            $stmt->execute(['id' => $id]);
            $compra = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$compra) {
                http_response_code(404);
                echo "Documento no encontrado.";
                return;
            }

            $stmtDet = $db->prepare("
                SELECT cd.*, pr.codigo, pr.descripcion
                FROM compras_detalles cd
                INNER JOIN productos pr ON cd.producto_id = pr.id
                WHERE cd.compra_id = :id
            ");
            $stmtDet->execute(['id' => $id]);
            $detalles = $stmtDet->fetchAll(\PDO::FETCH_ASSOC);

            $cfg = [];
            try {
                $cfg = $db->query("SELECT clave, valor FROM configuracion WHERE clave LIKE 'empresa_%'")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } catch(\Throwable $e) {}
            $empresa = $cfg['empresa_nombre'] ?? 'mi ERP Enterprise';
            $rif = $cfg['empresa_rif'] ?? 'J-00000000-0';

            $itemsHtml = '';
            foreach ($detalles as $d) {
                $itemsHtml .= "
                <tr>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0;font-family:monospace'>{$d['codigo']}</td>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0'>{$d['descripcion']}</td>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:center'>" . number_format($d['cantidad'], 2) . "</td>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right'>\$" . number_format($d['costo_unitario'], 2) . "</td>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:bold'>\$" . number_format($d['subtotal'], 2) . "</td>
                </tr>";
            }

            $tipoLabel = $compra['tipo_documento'] === 'ORDEN_COMPRA' ? 'ORDEN DE COMPRA' : 'FACTURA DE COMPRA';

            $body = "
            <div style='font-family:Arial,sans-serif;max-width:720px;margin:0 auto'>
                <div style='display:flex;justify-content:space-between;align-items:start;margin-bottom:20px'>
                    <div>
                        <h1 style='font-size:20px;font-weight:900;color:#1e293b;margin:0'>{$empresa}</h1>
                        <p style='color:#64748b;margin:2px 0;font-size:11px'>RIF: {$rif}</p>
                    </div>
                    <div style='text-align:right'>
                        <div style='background:#065f46;color:#fff;padding:6px 14px;border-radius:8px;font-size:11px;font-weight:bold'>{$tipoLabel}</div>
                        <p style='font-size:18px;font-weight:900;font-family:monospace;color:#1e293b;margin:4px 0'>{$compra['numero_factura']}</p>
                        <p style='font-size:11px;color:#64748b;margin:2px 0'>Fecha: {$compra['fecha_emision']}</p>
                        <p style='font-size:11px;color:#64748b;margin:2px 0'>Estado: <strong>{$compra['estado']}</strong></p>
                    </div>
                </div>

                <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:16px'>
                    <strong style='font-size:10px;color:#64748b;text-transform:uppercase'>Proveedor:</strong>
                    <p style='margin:2px 0;font-weight:bold;color:#1e293b'>{$compra['razon_social']}</p>
                    <p style='margin:1px 0;font-size:11px;color:#64748b'>RIF: {$compra['documento_fiscal']}</p>
                </div>

                <table style='width:100%;border-collapse:collapse;font-size:12px'>
                    <thead>
                        <tr style='background:#065f46;color:#fff'>
                            <th style='padding:8px;text-align:left'>Código</th>
                            <th style='padding:8px;text-align:left'>Descripción</th>
                            <th style='padding:8px;text-align:center'>Cant.</th>
                            <th style='padding:8px;text-align:right'>Costo Unit.</th>
                            <th style='padding:8px;text-align:right'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>{$itemsHtml}</tbody>
                </table>

                <div style='margin-top:16px;text-align:right'>
                    <table style='margin-left:auto;font-size:12px'>
                        <tr><td style='padding:3px 12px;color:#64748b'>Subtotal:</td><td style='font-family:monospace;font-weight:bold'>\$" . number_format($compra['subtotal_neto'] ?? 0, 2) . "</td></tr>
                        <tr><td style='padding:3px 12px;color:#64748b'>IVA:</td><td style='font-family:monospace'>\$" . number_format($compra['monto_iva'] ?? 0, 2) . "</td></tr>
                        <tr style='border-top:2px solid #065f46'>
                            <td style='padding:6px 12px;font-weight:900;font-size:14px'>TOTAL USD:</td>
                            <td style='font-family:monospace;font-weight:900;font-size:14px;color:#065f46'>\$" . number_format($compra['total_general'] ?? 0, 2) . "</td>
                        </tr>
                    </table>
                </div>
            </div>";

            $html = PdfEngine::wrapDocument($tipoLabel . ' ' . $compra['numero_factura'], $body, 'letter');
            PdfEngine::streamPdf($html, strtolower($compra['tipo_documento']) . '_' . $compra['numero_factura'] . '.html', true);
        } catch (Exception $e) {
            http_response_code(500);
            echo "Error: " . $e->getMessage();
        }
    }
}
