<?php
namespace App\Controllers;
use App\Core\Database;
use App\Core\TemplateEngine;
use PDO;
use Exception;
class FormatosController {
    public function listarFormatos(): void {
        $this->index();
    }

    public function guardarFormato(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        $this->update($id);
    }

    public function renderizarPrevio(): void {
        $this->preview();
    }

    public function index(): void {
       $db = Database::getConnection();
       $stmt = $db->query("SELECT id, tipo_documento, nombre_plantilla, ancho_papel_mm, updated_at FROM formatos_impresion");
       echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
   }
   public function show(int $id): void {
       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT * FROM formatos_impresion WHERE id = :id LIMIT 1");
       $stmt->execute(['id' => $id]);
       $formato = $stmt->fetch();
       if (!$formato) {
           http_response_code(404);
           echo json_encode(["status" => "error", "message" => "Formato no encontrado"]);
           return;
       }
       echo json_encode(["status" => "success", "data" => $formato]);
   }
   public function update(int $id): void {
       $input = json_decode(file_get_contents('php://input'), true);
       if (empty($input['cuerpo_html'])) {
           http_response_code(422);
           echo json_encode(["status" => "error", "message" => "El cuerpo HTML no puede estar vacío"]);
           return;
       }
       try {
           $db = Database::getConnection();
           $stmt = $db->prepare("
               UPDATE formatos_impresion 
               SET cuerpo_html = :html, ancho_papel_mm = :ancho 
               WHERE id = :id
           ");
           $stmt->execute([
               'html'  => $input['cuerpo_html'],
               'ancho' => (int)($input['ancho_papel_mm'] ?? 80),
               'id'    => $id
           ]);

           echo json_encode(["status" => "success", "message" => "Plantilla actualizada correctamente"]);
       } catch (Exception $e) {
           http_response_code(500);
           echo json_encode(["status" => "error", "message" => $e->getMessage()]);
       }
   }
   public function preview(): void {
       $input = json_decode(file_get_contents('php://input'), true);
       $rawHtml = $input['cuerpo_html'] ?? '';
       // Datos de prueba simulados
       $datosPrueba = [
           'numero_documento'  => 'FAC-00012480',
           'control_fiscal'    => '00-001294',
           'fecha_emision'     => date('d/m/Y H:i'),
           'condicion_pago'    => 'CONTADO',
           'cliente_nombre'    => 'DISTRIBUIDORA ANDINA C.A.',
           'cliente_rif'       => 'J-30998877-1',
           'cliente_direccion' => 'Av. Bolívar, Local 4, Valencia',
           'vendedor_nombre'   => 'Juan Pérez',
           'subtotal'          => '150.00',
           'monto_exento'      => '0.00',
           'base_imponible'    => '150.00',
           'monto_iva'         => '24.00',
           'total_general'     => '174.00',
           'total_general_bs'  => '6,351.00',
           'tasa_cambio'       => (string)Database::getTasaActualUsd(),
           'tabla_items'       => '
               <tr><td>1.0 x PROD-01 (Teclado Mecánico)</td><td style="text-align:right;">50.00</td></tr>
               <tr><td>2.0 x PROD-02 (Mouse Inalámbrico)</td><td style="text-align:right;">100.00</td></tr>
           ',
           'qr_fiscal_img'     => '<img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=DEMO_CUFE_QR">'
       ];
       // Se procesa mediante el motor para forzar el nombre de la licencia
       $htmlProcesado = TemplateEngine::renderFormat($rawHtml, $datosPrueba);
       header('Content-Type: text/html; charset=UTF-8');
       echo $htmlProcesado;
   }
}