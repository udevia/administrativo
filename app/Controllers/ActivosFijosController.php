<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivosFijosService;
use App\Core\Database;
use Exception;

class ActivosFijosController {
    public function listarActivos(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT af.*, c.nombre AS categoria_nombre, d.nombre AS departamento_nombre
                FROM activos_fijos af
                LEFT JOIN activos_categorias c ON af.categoria_id = c.id
                LEFT JOIN departamentos d ON af.departamento_id = d.id
                ORDER BY af.id DESC
            ");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function registrarActivo(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['descripcion']) || empty($input['costo_adquisicion_usd'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos.']);
                return;
            }

            $id = ActivosFijosService::crearActivo($input);
            echo json_encode(['status' => 'success', 'id' => $id, 'message' => 'Activo registrado.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function depreciarMensual(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            $ano = (int)($input['ano'] ?? date('Y'));
            $mes = (int)($input['mes'] ?? date('n'));
            $usuarioId = (int)($input['usuario_id'] ?? 1);

            $res = ActivosFijosService::ejecutarCierreDepreciacionMensual($ano, $mes, $usuarioId);
            echo json_encode($res);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
