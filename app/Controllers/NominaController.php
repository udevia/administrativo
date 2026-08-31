<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\NominaService;
use App\Core\Pdf\ReciboNominaPdf;
use App\Core\Database;
use Exception;

class NominaController {
    public function listarEmpleados(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT e.*
                FROM nomina_empleados e
                WHERE e.estado != 'LIQUIDADO'
                ORDER BY e.apellidos, e.nombres ASC
            ");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function guardarEmpleado(?int $id = null): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();
            $input = json_decode((string)file_get_contents('php://input'), true) ?? [];
            if (empty($input['nombres']) || empty($input['apellidos'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Nombres y apellidos son requeridos.']);
                return;
            }
            // Detect ID from URL param or body
            $empId = $id ?? (int)($input['id'] ?? 0);

            if ($empId > 0) {
                $stmt = $db->prepare("
                    UPDATE nomina_empleados SET
                        codigo = :codigo, nombres = :nombres, apellidos = :apellidos,
                        cedula = :cedula, fecha_ingreso = :fecha_ingreso,
                        cargo = :cargo,
                        sueldo_base_mensual = :sueldo_base_mensual,
                        bono_alimentacion_mensual = :bono_alimentacion_mensual,
                        numero_cuenta_bancaria = :numero_cuenta_bancaria, estado = :estado
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id' => $empId, ':codigo' => $input['codigo'] ?? '',
                    ':nombres' => $input['nombres'], ':apellidos' => $input['apellidos'],
                    ':cedula' => $input['cedula'] ?? '',
                    ':fecha_ingreso' => $input['fecha_ingreso'] ?? date('Y-m-d'),
                    ':cargo' => $input['cargo_nombre'] ?? '',
                    ':sueldo_base_mensual' => (float)($input['sueldo_base_mensual'] ?? 0),
                    ':bono_alimentacion_mensual' => (float)($input['bono_alimentacion_mensual'] ?? 0),
                    ':numero_cuenta_bancaria' => $input['cuenta_bancaria'] ?? '',
                    ':estado' => $input['estatus'] ?? 'ACTIVO'
                ]);
                echo json_encode(['status' => 'success', 'id' => $empId, 'message' => 'Empleado actualizado.']);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO nomina_empleados (codigo, nombres, apellidos, cedula,
                        fecha_ingreso, cargo,
                        sueldo_base_mensual, bono_alimentacion_mensual, numero_cuenta_bancaria,
                        estado)
                    VALUES (:codigo, :nombres, :apellidos, :cedula,
                        :fecha_ingreso, :cargo,
                        :sueldo_base_mensual, :bono_alimentacion_mensual, :numero_cuenta_bancaria,
                        :estado)
                ");
                $stmt->execute([
                    ':codigo' => $input['codigo'] ?? ('EMP-' . time()),
                    ':nombres' => $input['nombres'], ':apellidos' => $input['apellidos'],
                    ':cedula' => $input['cedula'] ?? '',
                    ':fecha_ingreso' => $input['fecha_ingreso'] ?? date('Y-m-d'),
                    ':cargo' => $input['cargo_nombre'] ?? '',
                    ':sueldo_base_mensual' => (float)($input['sueldo_base_mensual'] ?? 0),
                    ':bono_alimentacion_mensual' => (float)($input['bono_alimentacion_mensual'] ?? 0),
                    ':numero_cuenta_bancaria' => $input['cuenta_bancaria'] ?? '',
                    ':estado' => $input['estatus'] ?? 'ACTIVO'
                ]);
                echo json_encode(['status' => 'success', 'id' => (int)$db->lastInsertId(), 'message' => 'Empleado creado.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function prenomina(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true) ?? $_GET;
            $codigoPeriodo = $input['codigo_periodo'] ?? ('Q1-' . date('m-Y'));
            $fechaInicio = $input['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $input['fecha_fin'] ?? date('Y-m-15');
            $fechaPago = $input['fecha_pago'] ?? date('Y-m-15');
            $frecuencia = $input['frecuencia'] ?? 'QUINCENAL';
            $usuarioId = (int)($input['usuario_id'] ?? 1);

            $periodoId = NominaService::calcularPrenomina($codigoPeriodo, $fechaInicio, $fechaFin, $fechaPago, $frecuencia, $usuarioId);
            echo json_encode(['status' => 'success', 'data' => ['periodo_id' => $periodoId]]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function procesarCierre(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['periodo_id']) || empty($input['cuenta_bancaria_id'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Seleccione período y cuenta bancaria para el desembolso.']);
                return;
            }

            $res = NominaService::procesarPagoYCierre(
                (int)$input['periodo_id'],
                (int)$input['cuenta_bancaria_id'],
                (int)($input['usuario_id'] ?? 1)
            );

            echo json_encode(['status' => 'success', 'data' => $res, 'message' => 'Nómina liquidada con éxito.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function reciboPdf(int $prenominaId): void {
        try {
            ReciboNominaPdf::generarRecibo($prenominaId);
        } catch (Exception $e) {
            echo "<h1>Error generando recibo de nómina</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
