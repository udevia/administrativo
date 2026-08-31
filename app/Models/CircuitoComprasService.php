<?php
namespace App\Models;
use App\Core\Database;
use App\Models\InventarioService;
use App\Models\ComprasService;
use Exception;
class CircuitoComprasService {
   /**
    * FASE 2: Recepción física en Almacén (Carga en depósito virtual NO comercial)
    */
   public static function registrarRecepcion(array $cabecera, array $items): array {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           $ordenCompraId      = !empty($cabecera['orden_compra_id']) ? (int)$cabecera['orden_compra_id'] : null;
           $proveedorId        = (int)$cabecera['proveedor_id'];
           $depVirtualId       = (int)($cabecera['deposito_virtual_id'] ?? 99);
           $depDestinoFinalId  = (int)$cabecera['deposito_destino_final_id'];
           $usuarioId          = (int)($cabecera['usuario_id'] ?? 1);
           // 1. Insertar Cabecera de Recepción
           $stmt = $db->prepare("
               INSERT INTO recepciones_mercancia (
                   numero_recepcion, orden_compra_id, proveedor_id, deposito_virtual_id,
                   deposito_destino_final_id, guia_despacho_proveedor, fecha_recepcion,
                   usuario_almacen_id, estado, nota
               ) VALUES (
                   :num, :oc, :prov, :dep_v, :dep_f, :guia, :fec, :usr, 'EN_CUARENTENA', :nota
               )

           ");
           $stmt->execute([
               'num'   => $cabecera['numero_recepcion'],
               'oc'    => $ordenCompraId,
               'prov'  => $proveedorId,
               'dep_v' => $depVirtualId,
               'dep_f' => $depDestinoFinalId,
               'guia'  => $cabecera['guia_despacho_proveedor'] ?? null,
               'fec'   => $cabecera['fecha_recepcion'] ?? date('Y-m-d'),
               'usr'   => $usuarioId,
               'nota'  => $cabecera['nota'] ?? 'Recepción en muelle / almacén virtual'
           ]);
           $recepcionId = (int)$db->lastInsertId();
           // 2. Insertar Detalles y Cargar al Almacén Virtual
           $stmtDet = $db->prepare("
               INSERT INTO recepciones_mercancia_detalles (
                   recepcion_id, producto_id, cantidad_recibida, costo_pactado, lote, fecha_vencimiento
               ) VALUES (
                   :rec, :prod, :cant, :costo, :lote, :fvenc
               )
           ");
           foreach ($items as $item) {
               $cant = (float)$item['cantidad'];
               $prodId = (int)$item['producto_id'];
               $stmtDet->execute([
                   'rec'   => $recepcionId,
                   'prod'  => $prodId,
                   'cant'  => $cant,
                   'costo' => (float)$item['costo_unitario'],
                   'lote'  => $item['lote'] ?? null,
                   'fvenc' => $item['fecha_vencimiento'] ?? null
               ]);
               // Si viene de Orden de Compra, rebajar la "existencia por llegar"
               if ($ordenCompraId) {
                   $db->prepare("
                       UPDATE producto_deposito 
                       SET existencia_por_llegar = GREATEST(0, existencia_por_llegar - :cant) 
                       WHERE producto_id = :p AND deposito_id = :d
                   ")->execute(['cant' => $cant, 'p' => $prodId, 'd' => $depDestinoFinalId]);
               }
               // Cargar existencia física en el DEPÓSITO VIRTUAL (No disponible para ventas)
               InventarioService::registrarMovimiento(
                   $prodId,
                   $depVirtualId,
                   'ENTRADA_COMPRA',
                   'RECEPCION_VIRTUAL',
                   $cabecera['numero_recepcion'],
                   $cant,
                   (float)$item['costo_unitario'],
                   $usuarioId,
                   "Entrada a Almacén Virtual / Cuarentena"
               );
           }
            if ($ordenCompraId) {
                $db->prepare("UPDATE compras SET estado_flujo = 'RECEPCION_TOTAL' WHERE id = :id")->execute(['id' => $ordenCompraId]);
            }
           Database::commit();
           return ['status' => 'success', 'recepcion_id' => $recepcionId];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
   /**
    * FASE 3: Facturación Definitiva (Traslado a almacén comercial + Asiento CxP)

    */
   public static function facturarRecepcion(int $recepcionId, array $datosFactura): array {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           $stmt = $db->prepare("SELECT * FROM recepciones_mercancia WHERE id = :id FOR UPDATE");
           $stmt->execute(['id' => $recepcionId]);
           $rec = $stmt->fetch();
           if (!$rec || $rec['estado'] !== 'EN_CUARENTENA') {
               throw new Exception("La recepción no existe o ya ha sido procesada.");
           }
           $stmtItems = $db->prepare("SELECT * FROM recepciones_mercancia_detalles WHERE recepcion_id = :id");
           $stmtItems->execute(['id' => $recepcionId]);
           $detallesRec = $stmtItems->fetchAll();
           $depVirtualId   = (int)$rec['deposito_virtual_id'];
           $depComercialId = (int)$rec['deposito_destino_final_id'];
           $usuarioId      = (int)($datosFactura['usuario_id'] ?? 1);
           // 1. Descargar del virtual y trasladar al depósito comercial disponible
           foreach ($detallesRec as $d) {
               // Descarga de almacén virtual
               InventarioService::registrarMovimiento(
                   (int)$d['producto_id'],
                   $depVirtualId,
                   'AJUSTE_NEGATIVO',
                   'TRASLADO_COMPRA',
                   $datosFactura['numero_factura'],
                   (float)$d['cantidad_recibida'],
                   (float)$d['costo_pactado'],
                   $usuarioId,
                   "Salida de Cuarentena por Facturación"
               );
               // Entrada a depósito comercial (actualiza Kardex y recalcula costos)
               InventarioService::registrarMovimiento(
                   (int)$d['producto_id'],
                   $depComercialId,
                   'ENTRADA_COMPRA',
                   $datosFactura['tipo_documento'] ?? 'FACTURA_COMPRA',
                   $datosFactura['numero_factura'],
                   (float)$d['cantidad_recibida'],
                   (float)$d['costo_pactado'],
                   $usuarioId,
                   "Ingreso a Almacén Comercial Disponible"
               );
           }
           // 2. Registrar la Compra oficial y generar CxP
           $cabeceraCompra = [
               'tipo_documento'           => $datosFactura['tipo_documento'] ?? 'FACTURA_COMPRA',
               'numero_factura'           => $datosFactura['numero_factura'],
               'numero_control'           => $datosFactura['numero_control'] ?? null,
               'proveedor_id'             => (int)$rec['proveedor_id'],
               'deposito_id'              => $depComercialId,
               'usuario_id'               => $usuarioId,
               'fecha_emision'            => $datosFactura['fecha_emision'] ?? date('Y-m-d'),
               'fecha_recepcion'          => $rec['fecha_recepcion'],
               'fecha_vencimiento'        => $datosFactura['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+30 days')),
               'condicion_pago'           => $datosFactura['condicion_pago'] ?? 'CREDITO',
               'moneda_id'                => (int)($datosFactura['moneda_id'] ?? 1),
               'tasa_cambio'              => (float)($datosFactura['tasa_cambio'] ?? 1.0),
               'porcentaje_retencion_iva' => (float)($datosFactura['porcentaje_retencion_iva'] ?? 0.0),
               'porcentaje_retencion_islr'=> (float)($datosFactura['porcentaje_retencion_islr'] ?? 0.0),
               'documento_origen_id'      => $rec['orden_compra_id'],
               'recepcion_id'             => $recepcionId,
               'nota'                     => $datosFactura['nota'] ?? "Facturación de recepción {$rec['numero_recepcion']}"
           ];
           $itemsParaCompra = array_map(function($d) {

               return [
                   'producto_id'          => $d['producto_id'],
                   'cantidad'             => $d['cantidad_recibida'],
                   'costo_unitario'       => $d['costo_pactado'],
                   'porcentaje_descuento' => 0.0
               ];
           }, $detallesRec);
           $resCompra = ComprasService::procesarCompra($cabeceraCompra, $itemsParaCompra);
           // 3. Marcar recepción como procesada
           $db->prepare("UPDATE recepciones_mercancia SET estado = 'PROCESADA_COMPRA' WHERE id = :id")->execute(['id' => $recepcionId]);
           Database::commit();
           return $resCompra;
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
}