<?php
namespace App\Controllers;
use App\Models\TesoreriaService;
use Exception;
class TesoreriaController {
   public function procesarCobro(): void {
       $input = json_decode(file_get_contents('php://input'), true);
       if (empty($input['cabecera']) || empty($input['facturas']) || empty($input['formas_pago'])) {
           http_response_code(422);
           echo json_encode(["status" => "error", "message" => "Datos de cobranza incompletos."]);
           return;
       }
       try {
           $res = TesoreriaService::procesarCobranza($input['cabecera'], $input['facturas'], $input['formas_pago']);
           header('Content-Type: application/json');
           echo json_encode([

               "status"  => "success",
               "message" => "Cobranza procesada e ingresos bancarios asentados correctamente.",
               "data"    => $res
           ]);
       } catch (Exception $e) {
           http_response_code(400);
           header('Content-Type: application/json');
           echo json_encode(["status" => "error", "message" => $e->getMessage()]);
       }
   }

    public function debitarC2P(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['telefono']) || empty($input['cedula']) || empty($input['monto'])) {
            http_response_code(422);
            echo json_encode(["status" => "error", "message" => "Datos de C2P incompletos."]);
            return;
        }
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "message" => "Débito C2P simulado con éxito.",
            "referencia" => "C2P-" . time()
        ]);
    }
}