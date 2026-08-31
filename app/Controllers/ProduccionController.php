<?php
namespace App\Controllers;

use App\Models\ProduccionService;

class ProduccionController {

    public function listarFormulas() {
        header('Content-Type: application/json');
        try {
            $formulas = ProduccionService::listarFormulas();
            echo json_encode(['status' => 'success', 'data' => $formulas]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function guardarFormula() {
        header('Content-Type: application/json');
        $input = json_decode((string)file_get_contents('php://input'), true);
        try {
            $id = ProduccionService::guardarFormula($input);
            echo json_encode(['status' => 'success', 'data' => ['formula_id' => $id]]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function listarOrdenes() {
        header('Content-Type: application/json');
        try {
            $ordenes = ProduccionService::listarOrdenes();
            echo json_encode(['status' => 'success', 'data' => $ordenes]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function iniciarOrden() {
        header('Content-Type: application/json');
        $input = json_decode((string)file_get_contents('php://input'), true);
        try {
            $res = ProduccionService::iniciarOrden($input);
            echo json_encode(['status' => 'success', 'data' => $res]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function completarOrden() {
        header('Content-Type: application/json');
        $input = json_decode((string)file_get_contents('php://input'), true);
        try {
            ProduccionService::completarOrden((int)$input['orden_id'], (float)$input['cantidad_fabricada_real']);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
