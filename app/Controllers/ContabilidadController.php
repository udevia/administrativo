<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\BalancesContablesService;
use App\Core\ContabilidadEngine;
use App\Core\Database;
use Exception;

class ContabilidadController {
    public function planCuentas(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT *, IF(nivel >= 4, 1, 0) AS acepta_movimientos FROM contabilidad_puc ORDER BY codigo ASC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function balanceComprobacion(): void {
        header('Content-Type: application/json');
        try {
            $desde = $_GET['desde'] ?? date('Y-m-01');
            $hasta = $_GET['hasta'] ?? date('Y-m-d');
            $data = BalancesContablesService::balanceComprobacion($desde, $hasta);
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function asientos(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT c.*, u.nombre AS usuario_nombre,
                       (SELECT COUNT(*) FROM contabilidad_comprobantes_detalles WHERE comprobante_id = c.id) AS total_movimientos
                FROM contabilidad_comprobantes c
                LEFT JOIN usuarios u ON c.usuario_id = u.id
                ORDER BY c.id DESC
                LIMIT 100
            ");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function crearAsientoManual(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['renglones'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'El comprobante debe tener renglones.']);
                return;
            }

            $id = ContabilidadEngine::registrarAsiento(
                $input['tipo'] ?? 'DIARIO',
                $input['concepto'] ?? 'Asiento de Diario Manual',
                $input['documento_referencia'] ?? ('MAN-' . time()),
                $input['renglones'],
                (int)($input['usuario_id'] ?? 1),
                $input['fecha'] ?? date('Y-m-d')
            );

            echo json_encode(['status' => 'success', 'comprobante_id' => $id, 'message' => 'Comprobante registrado.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function detalleAsiento(int $id): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT d.*, p.codigo AS codigo_cuenta, p.descripcion AS nombre_cuenta
                FROM contabilidad_comprobantes_detalles d
                LEFT JOIN contabilidad_puc p ON p.id = d.cuenta_id
                WHERE d.comprobante_id = :id
                ORDER BY d.id ASC
            ");
            $stmt->execute([':id' => $id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
