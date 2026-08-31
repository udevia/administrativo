<?php
namespace App\Controllers;

use App\Models\ReportesService;
use Exception;

class ReportesController {
    
    public function consultar(string $tipo) {
        header('Content-Type: application/json');
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-t');

        try {
            $data = ReportesService::generarReporte($tipo, $desde, $hasta);
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}