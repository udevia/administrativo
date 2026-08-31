<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Producto;
use App\Models\InventarioService;
use App\Core\Database;
use PDO;
use Exception;

class InventarioController {
    public function listarProductos(): void {
        header('Content-Type: application/json');
        try {
            $catId = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;
            $q = $_GET['q'] ?? null;
            $productos = Producto::all($catId, $q);
            echo json_encode(['status' => 'success', 'data' => $productos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function verProducto(int $id): void {
        header('Content-Type: application/json');
        try {
            $producto = Producto::find($id);
            if (!$producto) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado']);
                return;
            }
            echo json_encode(['status' => 'success', 'data' => $producto]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function verKardex(int $productoId): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT k.*, d.descripcion AS deposito_nombre, u.nombre AS usuario_nombre
                FROM kardex_inventario k
                INNER JOIN depositos d ON k.deposito_id = d.id
                INNER JOIN usuarios u ON k.usuario_id = u.id
                WHERE k.producto_id = :p
                ORDER BY k.id DESC
                LIMIT 100
            ");
            $stmt->execute(['p' => $productoId]);
            $kardex = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $kardex]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function ajusteStock(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['producto_id']) || empty($input['deposito_id']) || empty($input['cantidad'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos para el ajuste.']);
                return;
            }

            $tipo = strtoupper((string)($input['tipo'] ?? 'AJUSTE_POSITIVO'));
            $res = InventarioService::registrarMovimiento(
                (int)$input['producto_id'],
                (int)$input['deposito_id'],
                $tipo,
                'AJUSTE',
                $input['documento_numero'] ?? ('AJ-' . time()),
                (float)$input['cantidad'],
                (float)($input['costo_unitario'] ?? 0.0),
                (int)($input['usuario_id'] ?? 1),
                $input['nota'] ?? 'Ajuste manual de inventario'
            );

            echo json_encode(['status' => 'success', 'data' => $res, 'message' => 'Ajuste de inventario registrado.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function traslado(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['producto_id']) || empty($input['deposito_origen_id']) || empty($input['deposito_destino_id']) || empty($input['cantidad'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos para el traslado.']);
                return;
            }

            $res = InventarioService::trasladoEntreDepositos(
                (int)$input['producto_id'],
                (int)$input['deposito_origen_id'],
                (int)$input['deposito_destino_id'],
                (float)$input['cantidad'],
                (int)($input['usuario_id'] ?? 1),
                $input['nota'] ?? 'Traslado entre depósitos'
            );

            echo json_encode(['status' => 'success', 'data' => $res, 'message' => 'Traslado completado exitosamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function listarDepositos(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM depositos WHERE estado = 1 ORDER BY codigo ASC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function listarCategorias(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM categorias WHERE estado = 1 ORDER BY descripcion ASC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}