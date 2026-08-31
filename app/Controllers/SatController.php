<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\SatService;
use App\Core\Database;
use Exception;

class SatController {
    public function listarOrdenes(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT ot.*, ts.nombre AS tipo_servicio_nombre, c.razon_social AS cliente_nombre,
                       ef.nombre AS estado_nombre, ef.color_hex AS estado_color
                FROM sat_ordenes_trabajo ot
                INNER JOIN sat_tipos_servicio ts ON ot.tipo_servicio_id = ts.id
                INNER JOIN clientes c ON ot.cliente_id = c.id
                INNER JOIN sat_estados_flujo ef ON ot.estado_id = ef.id
                ORDER BY ot.id DESC
                LIMIT 100
            ");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function crearOrden(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para crear la orden SAT.']);
                return;
            }
            $res = SatService::crearOrden($input);
            echo json_encode(['status' => 'success', 'data' => $res, 'message' => 'Orden de servicio creada.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function cambiarEstado(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['orden_id']) || empty($input['estado_id'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos.']);
                return;
            }
            $res = SatService::cambiarEstado(
                (int)$input['orden_id'],
                (int)$input['estado_id'],
                (int)($input['usuario_id'] ?? 1)
            );
            echo json_encode($res);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function facturarOrden(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['orden_id'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID de orden requerido.']);
                return;
            }

            $res = SatService::facturarOrdenEnPos(
                (int)$input['orden_id'],
                $input['tipo_documento'] ?? 'FACTURA',
                (int)($input['usuario_id'] ?? 1),
                (int)($input['moneda_id'] ?? 1),
                (float)($input['tasa_cambio'] ?? \App\Core\Database::getTasaActualUsd())
            );
            echo json_encode(['status' => 'success', 'data' => $res, 'message' => 'Orden facturada en POS.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/sat/actualizar — Actualiza diagnóstico y estado de una orden (Kanban)
     */
    public function actualizarOrden(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID de orden requerido.']);
                return;
            }
            $db = \App\Core\Database::getConnection();
            $fields = [];
            $params = ['id' => (int)$input['id']];

            if (isset($input['diagnostico_tecnico'])) {
                $fields[] = 'diagnostico_tecnico = :diag';
                $params['diag'] = $input['diagnostico_tecnico'];
            }
            if (isset($input['estado_id'])) {
                $fields[] = 'estado_id = :estado_id';
                $params['estado_id'] = (int)$input['estado_id'];
            }
            if (isset($input['accesorios_recibidos'])) {
                $fields[] = 'accesorios_recibidos = :accesorios';
                $params['accesorios'] = $input['accesorios_recibidos'];
            }
            if (empty($fields)) {
                echo json_encode(['status' => 'success', 'message' => 'Sin cambios.']);
                return;
            }

            $sql = "UPDATE sat_ordenes_trabajo SET " . implode(', ', $fields) . " WHERE id = :id";
            $db->prepare($sql)->execute($params);
            echo json_encode(['status' => 'success', 'message' => 'Orden actualizada correctamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}

