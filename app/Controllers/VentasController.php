<?php
namespace App\Controllers;

use App\Models\VentasService;
use App\Core\PdfEngine;
use App\Core\Database;
use Exception;

class VentasController {

    public function procesar(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $cabecera = $input['cabecera'] ?? $input;
        $items = $input['items'] ?? [];

        if (empty($cabecera['tipo_documento']) || empty($cabecera['cliente_id']) || empty($items)) {
            http_response_code(422);
            echo json_encode(["status" => "error", "message" => "Datos de factura incompletos."]);
            return;
        }
        try {
            $resultado = VentasService::procesarVenta($cabecera, $items);
            header('Content-Type: application/json');
            echo json_encode([
                "status"  => "success",
                "message" => "Documento procesado con éxito.",
                "data"    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    public function procesarVenta(): void {
        $this->procesar();
    }

    /**
     * GET /api/ventas/historial?tipo=FACTURA&desde=&hasta=&cliente_id=
     */
    public function historial(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $tipo     = $_GET['tipo'] ?? null;
            $desde    = $_GET['desde'] ?? date('Y-m-01');
            $hasta    = $_GET['hasta'] ?? date('Y-m-d');
            $clienteId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : null;

            $where = ["v.fecha_emision BETWEEN :d1 AND :d2"];
            $params = ['d1' => $desde, 'd2' => $hasta];

            if ($tipo) {
                $where[] = "v.tipo_documento = :tipo";
                $params['tipo'] = $tipo;
            }
            if ($clienteId) {
                $where[] = "v.cliente_id = :cid";
                $params['cid'] = $clienteId;
            }

            $whereStr = implode(' AND ', $where);
            $stmt = $db->prepare("
                SELECT v.id, v.tipo_documento, v.numero_documento, v.numero_factura,
                       v.fecha_emision, v.condicion_pago, v.estado,
                       c.razon_social AS cliente_nombre, c.documento_fiscal,
                       v.subtotal_neto, v.monto_iva, v.total_general, v.saldo_pendiente,
                       v.created_at
                FROM ventas v
                INNER JOIN clientes c ON v.cliente_id = c.id
                WHERE {$whereStr}
                ORDER BY v.id DESC
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
     * GET /api/ventas/{id}/documento — Impresión HTML del documento
     */
    public function imprimirDocumento(int $id): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT v.*, c.razon_social, c.documento_fiscal, c.direccion_fiscal,
                       c.email, c.telefono
                FROM ventas v
                INNER JOIN clientes c ON v.cliente_id = c.id
                WHERE v.id = :id
            ");
            $stmt->execute(['id' => $id]);
            $venta = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$venta) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Documento no encontrado.']);
                return;
            }

            $stmtDet = $db->prepare("
                SELECT d.*, p.codigo, p.descripcion
                FROM ventas_detalles d
                INNER JOIN productos p ON d.producto_id = p.id
                WHERE d.venta_id = :id
            ");
            $stmtDet->execute(['id' => $id]);
            $detalles = $stmtDet->fetchAll(\PDO::FETCH_ASSOC);

            // Obtener configuración de empresa
            $cfg = [];
            try {
                $rows = $db->query("SELECT clave, valor FROM configuracion WHERE clave LIKE 'empresa_%'")->fetchAll(\PDO::FETCH_KEY_PAIR);
                $cfg = $rows ?: [];
            } catch(\Throwable $e) {}

            $empresa = $cfg['empresa_nombre'] ?? 'mi ERP Enterprise';
            $rif = $cfg['empresa_rif'] ?? 'J-00000000-0';
            $dir = $cfg['empresa_direccion'] ?? 'Caracas, Venezuela';

            $itemsHtml = '';
            foreach ($detalles as $d) {
                $sub = number_format($d['subtotal'], 2);
                $tot = number_format($d['total'], 2);
                $pu  = number_format($d['precio_unitario'], 2);
                $cant = number_format($d['cantidad'], 2);
                $itemsHtml .= "
                <tr>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0;font-family:monospace'>{$d['codigo']}</td>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0'>{$d['descripcion']}</td>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:center'>{$cant}</td>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right'>\${$pu}</td>
                    <td style='padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:bold'>\${$sub}</td>
                </tr>";
            }

            $tipoLabel = match($venta['tipo_documento']) {
                'FACTURA'       => 'FACTURA FISCAL',
                'NOTA_ENTREGA'  => 'NOTA DE ENTREGA',
                'PRESUPUESTO'   => 'PRESUPUESTO',
                'PEDIDO'        => 'PEDIDO / APARTADO',
                'DEVOLUCION'    => 'NOTA DE CRÉDITO / DEVOLUCIÓN',
                default         => $venta['tipo_documento']
            };

            $body = "
            <div style='font-family:Arial,sans-serif;max-width:720px;margin:0 auto'>
                <div style='display:flex;justify-content:space-between;align-items:start;margin-bottom:20px'>
                    <div>
                        <h1 style='font-size:20px;font-weight:900;color:#1e293b;margin:0'>{$empresa}</h1>
                        <p style='color:#64748b;margin:2px 0;font-size:11px'>RIF: {$rif}</p>
                        <p style='color:#64748b;margin:2px 0;font-size:11px'>{$dir}</p>
                    </div>
                    <div style='text-align:right'>
                        <div style='background:#1e3a5f;color:#fff;padding:6px 14px;border-radius:8px;font-size:11px;font-weight:bold;letter-spacing:1px'>{$tipoLabel}</div>
                        <p style='font-size:18px;font-weight:900;font-family:monospace;color:#1e293b;margin:4px 0'>{$venta['numero_documento']}</p>
                        <p style='font-size:11px;color:#64748b;margin:2px 0'>Fecha: {$venta['fecha_emision']}</p>
                        <p style='font-size:11px;color:#64748b;margin:2px 0'>Estado: <strong>{$venta['estado']}</strong></p>
                    </div>
                </div>

                <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:16px'>
                    <strong style='font-size:10px;color:#64748b;text-transform:uppercase'>Cliente:</strong>
                    <p style='margin:2px 0;font-weight:bold;color:#1e293b'>{$venta['razon_social']}</p>
                    <p style='margin:1px 0;font-size:11px;color:#64748b'>RIF/CI: {$venta['documento_fiscal']}</p>
                    <p style='margin:1px 0;font-size:11px;color:#64748b'>Condición: {$venta['condicion_pago']}</p>
                </div>

                <table style='width:100%;border-collapse:collapse;font-size:12px'>
                    <thead>
                        <tr style='background:#1e3a5f;color:#fff'>
                            <th style='padding:8px;text-align:left'>Código</th>
                            <th style='padding:8px;text-align:left'>Descripción</th>
                            <th style='padding:8px;text-align:center'>Cant.</th>
                            <th style='padding:8px;text-align:right'>P. Unit.</th>
                            <th style='padding:8px;text-align:right'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>{$itemsHtml}</tbody>
                </table>

                <div style='margin-top:16px;text-align:right'>
                    <table style='margin-left:auto;font-size:12px'>
                        <tr><td style='padding:3px 12px;color:#64748b'>Subtotal Neto:</td><td style='font-family:monospace;font-weight:bold'>\$" . number_format($venta['subtotal_neto'], 2) . "</td></tr>
                        <tr><td style='padding:3px 12px;color:#64748b'>Monto Exento:</td><td style='font-family:monospace'>\$" . number_format($venta['monto_exento'], 2) . "</td></tr>
                        <tr><td style='padding:3px 12px;color:#64748b'>Base Imponible:</td><td style='font-family:monospace'>\$" . number_format($venta['base_imponible'], 2) . "</td></tr>
                        <tr><td style='padding:3px 12px;color:#64748b'>IVA (16%):</td><td style='font-family:monospace'>\$" . number_format($venta['monto_iva'], 2) . "</td></tr>
                        <tr style='border-top:2px solid #1e293b'>
                            <td style='padding:6px 12px;font-weight:900;font-size:14px'>TOTAL USD:</td>
                            <td style='font-family:monospace;font-weight:900;font-size:14px;color:#1e3a5f'>\$" . number_format($venta['total_general'], 2) . "</td>
                        </tr>
                    </table>
                </div>

                <div style='margin-top:24px;padding-top:12px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;text-align:center'>
                    Documento generado por mi ERP Enterprise | {$empresa} | {$rif}
                </div>
            </div>";

            $html = PdfEngine::wrapDocument($tipoLabel . ' - ' . $venta['numero_documento'], $body, 'letter');
            PdfEngine::streamPdf($html, strtolower($venta['tipo_documento']) . '_' . $venta['numero_documento'] . '.html', true);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/ventas/{id}/convertir — Convierte un documento en otro tipo
     * Body: {"nuevo_tipo": "FACTURA"}
     */
    public function convertirDocumento(int $id): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $nuevoTipo = $input['nuevo_tipo'] ?? null;
            $tiposValidos = ['FACTURA', 'NOTA_ENTREGA', 'PEDIDO', 'PRESUPUESTO'];
            if (!$nuevoTipo || !in_array($nuevoTipo, $tiposValidos)) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'Tipo de documento destino inválido.']);
                return;
            }
            $resultado = \App\Models\DocumentoVentaService::convertirDocumentoOrigen($id, $nuevoTipo, 1);
            echo json_encode([
                'status'  => 'success',
                'message' => "Documento convertido a {$nuevoTipo} exitosamente.",
                'data'    => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/ventas/{id}/anular
     */
    public function anularDocumento(int $id): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE ventas SET estado = 'ANULADA' WHERE id = :id AND estado != 'ANULADA'");
            $stmt->execute(['id' => $id]);
            if ($stmt->rowCount() === 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'El documento no existe o ya estaba anulado.']);
                return;
            }
            echo json_encode(['status' => 'success', 'message' => 'Documento anulado correctamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function resumenCierreZ(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $stmt = $db->prepare("
                SELECT 
                    COUNT(id) AS total_facturas,
                    COALESCE(SUM(subtotal_neto), 0) AS subtotal_neto,
                    COALESCE(SUM(monto_iva), 0) AS total_iva,
                    COALESCE(SUM(total_general), 0) AS total_general
                FROM ventas
                WHERE fecha_emision = :f AND estado != 'ANULADA'
            ");
            $stmt->execute(['f' => $fecha]);
            $resumen = $stmt->fetch(\PDO::FETCH_ASSOC);

            $stmtFormas = $db->prepare("
                SELECT fp.forma_pago, COALESCE(SUM(fp.monto), 0) AS total_recaudado
                FROM recibos_cobranza_formas_pago fp
                JOIN recibos_cobranza r ON fp.recibo_id = r.id
                WHERE r.fecha = :f
                GROUP BY fp.forma_pago
            ");
            $stmtFormas->execute(['f' => $fecha]);
            $formasPago = $stmtFormas->fetchAll(\PDO::FETCH_KEY_PAIR);

            $resumen['formas_pago'] = [
                'EFECTIVO'      => (float)($formasPago['EFECTIVO'] ?? 0.0),
                'TRANSFERENCIA' => (float)($formasPago['TRANSFERENCIA'] ?? 0.0),
                'PUNTO_VENTA'   => (float)($formasPago['PUNTO_VENTA'] ?? 0.0),
                'PAGO_MOVIL'    => (float)($formasPago['PAGO_MOVIL'] ?? 0.0),
                'ZELLE'         => (float)($formasPago['ZELLE'] ?? 0.0),
            ];

            echo json_encode([
                "status" => "success",
                "data"   => $resumen
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }
}
