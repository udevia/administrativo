<?php
namespace App\Controllers;
use App\Models\ImportadorMasivoService;
use Exception;
class MigracionController {
    public function analizarArchivo(): void {
        $this->previsualizar();
    }

    public function ejecutarMigracion(): void {
        $this->procesar();
    }

    public function descargarPlantilla(string $entidad): void {
       try {
           $csv = ImportadorMasivoService::generarCsvPlantilla($entidad);
           header('Content-Type: text/csv; charset=UTF-8');
           header("Content-Disposition: attachment; filename=\"plantilla_{$entidad}.csv\"");
           // Inyectar BOM para compatibilidad directa con Microsoft Excel en español
           echo "\xEF\xBB\xBF" . $csv;
           exit;
       } catch (Exception $e) {
           http_response_code(400);
           echo json_encode(["status" => "error", "message" => $e->getMessage()]);
       }
   }
   public function previsualizar(): void {
       $entidad = $_POST['entidad'] ?? '';
       $archivo = $_FILES['archivo'] ?? null;
       if (!$archivo || empty($archivo['tmp_name'])) {
           http_response_code(422);
           echo json_encode(["status" => "error", "message" => "Debe adjuntar un archivo válido."]);
           return;
       }
       try {
           $analisis = ImportadorMasivoService::analizarArchivo($entidad, $archivo['tmp_name']);
           header('Content-Type: application/json');
           echo json_encode(["status" => "success", "data" => $analisis]);
       } catch (Exception $e) {
           http_response_code(400);
           echo json_encode(["status" => "error", "message" => $e->getMessage()]);
       }
   }
   public function procesar(): void {

       $input = json_decode(file_get_contents('php://input'), true);
       $entidad = $input['entidad'] ?? '';
       $filas = $input['filas'] ?? [];
       if (empty($entidad) || empty($filas)) {
           http_response_code(422);
           echo json_encode(["status" => "error", "message" => "Datos incompletos para procesar."]);
           return;
       }
       try {
           $resultado = ImportadorMasivoService::procesarImportacion($entidad, $filas);
           header('Content-Type: application/json');
           echo json_encode($resultado);
       } catch (Exception $e) {
           http_response_code(400);
           echo json_encode(["status" => "error", "message" => $e->getMessage()]);
       }
   }
}