<?php
namespace App\Controllers;
use App\Models\AprovisionamientoService;
use App\Models\OrdenCompraService;
use Exception;
class AprovisionamientoController {
    public function consultarSugerencias(): void {
        $this->sugerencias();
    }

    public function generarOrdenAutomatica(): void {
        $this->generarOrdenDesdeSugerencia();
    }

    public function sugerencias(): void {
       $dias     = (int)($_GET['dias_historico'] ?? 30);
       $provId   = !empty($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : null;
       $catId    = !empty($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;
       $datos = AprovisionamientoService::calcularSugerenciaCompras($dias, $provId, $catId);
       
       header('Content-Type: application/json');
       echo json_encode(["status" => "success", "data" => $datos]);
   }
   /**
    * Convierte los renglones seleccionados de la sugerencia en una Orden de Compra formal (Fase 1)
    */
   public function generarOrdenDesdeSugerencia(): void {
       $input = json_decode(file_get_contents('php://input'), true);
       try {
           $proveedorId = (int)$input['proveedor_id'];
           $itemsSeleccionados = $input['items'] ?? [];
           if (empty($proveedorId) || empty($itemsSeleccionados)) {
               throw new Exception("Debe seleccionar un proveedor e ítems para generar la orden.");
           }
           $cabecera = [
               'numero_orden'            => 'OC-AUTO-' . date('ymd') . '-' . rand(100, 999),
               'proveedor_id'            => $proveedorId,
               'deposito_id'             => (int)($input['deposito_id'] ?? 1),
               'usuario_id'              => (int)($input['usuario_id'] ?? 1),
               'fecha_emision'           => date('Y-m-d'),
               'fecha_entrega_esperada'  => date('Y-m-d', strtotime('+7 days')),
               'fecha_vencimiento'       => date('Y-m-d', strtotime('+37 days')),

               'condicion_pago'          => 'CREDITO',
               'nota'                    => 'Orden generada automáticamente por asistente de demanda histórica.'
           ];
           $itemsFormateados = array_map(function($i) {
               return [
                   'producto_id'    => (int)$i['producto_id'],
                   'cantidad'       => (float)$i['cantidad_a_pedir'],
                   'costo_unitario' => (float)$i['costo_unitario_estimado']
               ];
           }, $itemsSeleccionados);
           $resultado = OrdenCompraService::crearOrden($cabecera, $itemsFormateados);
           header('Content-Type: application/json');
           echo json_encode([
               "status"  => "success",
               "message" => "Orden de compra generada exitosamente. Mercancía colocada en tránsito.",
               "data"    => $resultado
           ]);
       } catch (Exception $e) {
           http_response_code(400);
           header('Content-Type: application/json');
           echo json_encode(["status" => "error", "message" => $e->getMessage()]);
       }
   }
}