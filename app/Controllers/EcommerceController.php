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
}
