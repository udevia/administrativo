<?php
namespace App\Controllers;
use App\Core\Database;

use App\Models\DocumentoVentaService;
use Exception;
class ApiPreventaController {
    public function sincronizarCatalogo(): void {
       $vendedorId = (int)($_GET['vendedor_id'] ?? 1);
       $this->syncCatalogo($vendedorId);
   }

   /**
    * Sincronización inicial para trabajo offline/online del vendedor
    */
   public function syncCatalogo(int $vendedorId): void {
       $db = Database::getConnection();
       // 1. Clientes asignados a la zona del vendedor
       $stmtCli = $db->prepare("
           SELECT id, codigo, razon_social, documento_fiscal, direccion_fiscal, limite_credito, saldo_actual, lista_pre
           FROM clientes 
           WHERE (vendedor_id = :v OR vendedor_id IS NULL) AND estado = 1
           ORDER BY razon_social ASC
       ");
       $stmtCli->execute(['v' => $vendedorId]);
       $clientes = $stmtCli->fetchAll();
       // 2. Productos con stock real disponible (restando lo comprometido)
       $stmtProd = $db->query("
           SELECT 
               p.id, p.codigo, p.codigo_barra, p.descripcion, p.porcentaje_iva, p.exento_iva,
               p.precio_a, p.precio_b, p.precio_c, p.precio_d,
               COALESCE(SUM(pd.existencia - pd.existencia_comprometida), 0) AS stock_disponible
           FROM productos p
           LEFT JOIN producto_deposito pd ON p.id = pd.producto_id
           WHERE p.estado = 1
           GROUP BY p.id
       ");
       $productos = $stmtProd->fetchAll();
       header('Content-Type: application/json');
       echo json_encode([
           "status" => "success",
           "data" => [
               "clientes"  => $clientes,
               "productos" => $productos,
               "tasa"      => Database::getTasaActualUsd()
           ]
       ]);
   }
   /**
    * Levanta el pedido/apartado desde la ruta y compromete inventario inmediatamente
    */
   public function enviarPedido(): void {
       $input = json_decode(file_get_contents('php://input'), true);
       try {
           $cabecera = [
               'tipo_documento'   => 'APARTADO', // Bloquea y compromete stock de inmediato
               'numero_documento' => 'PREV-' . date('ymd') . '-' . rand(100, 999),
               'cliente_id'       => (int)$input['cliente_id'],
               'vendedor_id'      => (int)$input['vendedor_id'],
               'deposito_id'      => (int)($input['deposito_id'] ?? 1),
               'usuario_id'       => 1,
               'fecha_emision'    => date('Y-m-d'),
               'fecha_vencimiento'=> date('Y-m-d', strtotime('+3 days')), // 72 horas de reserva
               'condicion_pago'   => $input['condicion_pago'] ?? 'CREDITO',
               'moneda_id'        => 1,
               'tasa_cambio'      => (float)($input['tasa_cambio'] ?? 1.0),
               'nota'             => 'Preventa Móvil Ruta: ' . ($input['observacion'] ?? 'Sin nota')
           ];
           $resultado = DocumentoVentaService::procesar($cabecera, $input['items']);
           header('Content-Type: application/json');
           echo json_encode([
               "status"  => "success",
               "message" => "Pedido/Apartado transmitido. Inventario comprometido con éxito.",
               "data"    => $resultado
           ]);

       } catch (Exception $e) {
           http_response_code(400);
           header('Content-Type: application/json');
           echo json_encode(["status" => "error", "message" => $e->getMessage()]);
       }
   }
}