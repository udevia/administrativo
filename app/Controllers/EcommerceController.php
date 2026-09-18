<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Producto;
use App\Models\DocumentoVentaService;
use App\Core\Database;
use Exception;

class EcommerceController {
    public function catalogo(): void {
        header('Content-Type: application/json');
        try {
            $q = $_GET['q'] ?? null;
            $productos = Producto::all(null, $q);
            $datos = array_map(function($p) {
                return [
                    'id'              => $p['id'],
                    'codigo'          => $p['codigo'],
                    'descripcion'     => $p['descripcion'],
                    'categoria_nombre'=> $p['categoria_nombre'] ?? 'General',
                    'precio_venta'    => (float)$p['precio_a'],
                    'stock_disponible'=> (float)$p['stock_disponible'],
                    'imagen_url'      => null
                ];
            }, $productos);

            echo json_encode(['status' => 'success', 'data' => $datos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function crearPedidoWeb(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['items'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'El pedido debe contener al menos un producto.']);
                return;
            }

            $tasa = (float)($input['tasa_cambio'] ?? \App\Core\Database::getTasaActualUsd());
            $items = array_map(function($i) {
                return [
                    'producto_id'     => (int)$i['producto_id'],
                    'cantidad'        => (float)$i['cantidad'],
                    'precio_unitario' => (float)$i['precio_unitario'],
                    'descuento'       => 0.0
                ];
            }, $input['items']);

            $numPedido = 'WEB-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(3)), 0, 4);

            $payload = [
                'tipo_documento'   => 'PEDIDO',
                'numero_documento' => $numPedido,
                'cliente_id'       => (int)($input['cliente_id'] ?? 1),
                'vendedor_id'      => 1,
                'deposito_id'      => 1,
                'usuario_id'       => 1,
                'moneda_id'        => 1,
                'tasa_cambio'      => $tasa,
                'condicion_pago'   => 'CONTADO',
                'nota'             => "Pedido Web E-commerce - Ref: " . ($input['referencia_pago'] ?? 'N/A') . " (" . ($input['metodo_pago'] ?? 'PAGO_MOVIL') . ")",
                'items'            => $items
            ];

            $res = DocumentoVentaService::procesar($payload);

            echo json_encode([
                'status'       => 'success',
                'numero_orden' => $numPedido,
                'data'         => $res,
                'message'      => 'Pedido registrado exitosamente con reserva de inventario.'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/ecommerce/pedidos-por-facturar
     * Lista los pedidos web cuyo pago fue recibido/validado y que aún no
     * tienen factura fiscal emitida (para el panel del POS).
     */
    public function pedidosPorFacturar(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT ep.id, ep.numero_orden_web, ep.metodo_pago, ep.referencia_pago,
                       ep.monto_total_usd, ep.tasa_cambio, ep.monto_total_bs,
                       ep.estado_pago, ep.estado_despacho, ep.venta_apartado_id,
                       ep.created_at AS fecha_pedido,
                       eu.nombre_completo AS cliente_nombre,
                       eu.documento_identidad AS cliente_doc,
                       v.numero_documento AS numero_apartado
                FROM ecommerce_pedidos ep
                INNER JOIN ecommerce_usuarios eu ON eu.id = ep.usuario_web_id
                INNER JOIN ventas v ON v.id = ep.venta_apartado_id
                LEFT JOIN ventas f
                       ON f.documento_origen_id = ep.venta_apartado_id
                      AND f.tipo_documento = 'FACTURA'
                WHERE ep.estado_pago IN ('PENDIENTE_REVISION', 'VERIFICADO')
                  AND ep.estado_despacho NOT IN ('CANCELADO', 'ENVIADO', 'ENTREGADO')
                  AND f.id IS NULL
                ORDER BY (ep.estado_pago = 'VERIFICADO') DESC, ep.id DESC
            ");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/ecommerce/facturar
     * Emite la factura fiscal convirtiendo el APARTADO del pedido web.
     * Body: { pedido_id: int }
     */
    public function facturarPedido(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true) ?? [];
            $pedidoId = (int)($input['pedido_id'] ?? 0);
            if ($pedidoId <= 0) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'pedido_id requerido.']);
                return;
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT ep.*, v.numero_documento AS numero_apartado
                FROM ecommerce_pedidos ep
                INNER JOIN ventas v ON v.id = ep.venta_apartado_id
                WHERE ep.id = :id
            ");
            $stmt->execute(['id' => $pedidoId]);
            $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$pedido) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Pedido web no encontrado.']);
                return;
            }
            if (in_array($pedido['estado_despacho'], ['CANCELADO', 'ENVIADO', 'ENTREGADO'])) {
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'El pedido ya no está disponible para facturar (estado: ' . $pedido['estado_despacho'] . ').']);
                return;
            }
            $chk = $db->prepare("SELECT id FROM ventas WHERE documento_origen_id = :vid AND tipo_documento = 'FACTURA' LIMIT 1");
            $chk->execute(['vid' => (int)$pedido['venta_apartado_id']]);
            if ($chk->fetch()) {
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'Este pedido ya tiene factura fiscal emitida.']);
                return;
            }

            // Convertir el APARTADO en FACTURA (descuenta stock + kardex)
            $res = DocumentoVentaService::convertirDocumentoOrigen((int)$pedido['venta_apartado_id'], 'FACTURA', 1);

            // Avanzar el flujo de despacho
            if ($pedido['estado_despacho'] === 'RECIBIDO') {
                $db->prepare("UPDATE ecommerce_pedidos SET estado_despacho = 'EN_PREPARACION' WHERE id = :id")
                   ->execute(['id' => $pedidoId]);
            }

            echo json_encode([
                'status'         => 'success',
                'message'        => 'Factura fiscal emitida para el pedido web ' . $pedido['numero_orden_web'] . '.',
                'numero_factura' => $res['numero_documento'] ?? null,
                'venta_id'       => $res['venta_id'] ?? null,
                'total_usd'      => $res['total'] ?? $pedido['monto_total_usd']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
