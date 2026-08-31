<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\InstallerService;
use Exception;

class InstallerController {
    public function checkReq(): void {
        header('Content-Type: application/json');
        try {
            $data = InstallerService::checkRequisitos();
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function dbSetup(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Datos de base de datos inválidos.']);
                return;
            }
            $res = InstallerService::instalarBaseDatos($input);
            echo json_encode(['status' => 'success', 'data' => $res]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function licenseUpload(): void {
        header('Content-Type: application/json');
        try {
            if (!isset($_FILES['licencia'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Debe adjuntar un archivo .lic']);
                return;
            }
            $res = InstallerService::procesarLicencia($_FILES['licencia']);
            echo json_encode(['status' => 'success', 'data' => $res]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function adminSetup(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (!$input || empty($input['usuario']) || empty($input['password'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Complete usuario y contraseña de administrador.']);
                return;
            }
            $res = InstallerService::finalizarInstalacion($input);
            echo json_encode(['status' => 'success', 'data' => $res]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
