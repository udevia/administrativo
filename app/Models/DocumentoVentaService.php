<?php
namespace App\Models;
use App\Core\Database;
use App\Models\InventarioService;
use App\Models\Cliente;
use App\Models\VentasService;
use Exception;
class DocumentoVentaService {
   /**
    * Convierte un documento de origen (APARTADO, PEDIDO, PRESUPUESTO, FACTURA...)
    * en un nuevo documento del tipo indicado, copiando sus renglones.
    *
    * Usado por:
    *  - POST /api/ventas/{id}/convertir (conversión manual en Facturación)
    *  - POST /api/ecommerce/facturar (emisión de factura fiscal desde
    *    el apartado de un pedido web validado en el POS)
    */
   public static function convertirDocumentoOrigen(int $origenId, string $nuevoTipo, int $usuarioId): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT * FROM ventas WHERE id = :id");
       $stmt->execute(['id' => $origenId]);
       $doc = $stmt->fetch(\PDO::FETCH_ASSOC);
       if (!$doc) {
           throw new Exception("Documento origen ID {$origenId} no encontrado.");
       }

       // Obtener detalles del documento origen
       $stmtDet = $db->prepare("SELECT * FROM ventas_detalles WHERE venta_id = :id");
       $stmtDet->execute(['id' => $origenId]);
       $detalles = $stmtDet->fetchAll(\PDO::FETCH_ASSOC);
       if (empty($detalles)) {
           throw new Exception("El documento origen no tiene renglones para convertir.");
       }

       // Crear nuevo documento
       $nuevoCabecera = $doc;
       $nuevoCabecera['tipo_documento'] = $nuevoTipo;
       $nuevoCabecera['numero_documento'] = strtoupper(substr($nuevoTipo, 0, 3)) . '-' . date('ymd') . '-' . rand(1000, 9999);
       $nuevoCabecera['documento_origen_id'] = $origenId;
       $nuevoCabecera['usuario_id'] = $usuarioId;
       $nuevoCabecera['fecha_emision'] = date('Y-m-d');
       $nuevoCabecera['control_fiscal'] = null;
       unset($nuevoCabecera['id'], $nuevoCabecera['created_at'], $nuevoCabecera['updated_at']);
       if (isset($nuevoCabecera['estado'])) {
           unset($nuevoCabecera['estado']); // VentasService lo calcula según la condición de pago
       }

       $nuevosItems = array_map(fn($d) => [
           'producto_id'         => $d['producto_id'],
           'cantidad'            => $d['cantidad'],
           'precio_unitario'     => $d['precio_unitario'],
           'porcentaje_descuento'=> $d['porcentaje_descuento'] ?? 0,
           'porcentaje_iva'      => $d['porcentaje_iva']
       ], $detalles);

       return VentasService::procesarVenta($nuevoCabecera, $nuevosItems);
   }
   public static function procesar(array $cabecera, array $items): array {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           $tipoDoc    = $cabecera['tipo_documento'];

           $clienteId  = (int)$cabecera['cliente_id'];
           $depositoId = (int)$cabecera['deposito_id'];
           $usuarioId  = (int)($cabecera['usuario_id'] ?? 1);
           $docOrigenId= !empty($cabecera['documento_origen_id']) ? (int)$cabecera['documento_origen_id'] : null;
           // 1. Validar disponibilidad de stock según tipo de documento
           foreach ($items as $item) {
               $productoId = (int)$item['producto_id'];
               $cantidad   = (float)$item['cantidad'];
               $stmtStock = $db->prepare("
                   SELECT existencia, existencia_comprometida 
                   FROM producto_deposito 
                   WHERE producto_id = :p AND deposito_id = :d 
                   FOR UPDATE
               ");
               $stmtStock->execute(['p' => $productoId, 'd' => $depositoId]);
               $stockActual = $stmtStock->fetch();
               $existenciaReal = (float)($stockActual['existencia'] ?? 0);
               $comprometido   = (float)($stockActual['existencia_comprometida'] ?? 0);
               $disponible     = $existenciaReal - $comprometido;
               // Si es pedido o apartado, validamos stock disponible para comprometer
               if (in_array($tipoDoc, ['PEDIDO', 'APARTADO'])) {
                   if ($disponible < $cantidad) {
                       throw new Exception("Stock insuficiente para apartar/pedir. Disponible: {$disponible}, Solicitado: {$cantidad}");
                   }
               }
               // Si es factura o nota directa (sin origen previo), validamos stock real
               if (in_array($tipoDoc, ['FACTURA', 'NOTA_ENTREGA']) && empty($docOrigenId)) {
                   if ($disponible < $cantidad) {
                       throw new Exception("Stock insuficiente para despacho inmediato. Disponible: {$disponible}, Requerido: {$cantidad}");
                   }
               }
           }
           // 2. Calcular importes
           $subtotalNeto = 0.0;
           $baseImponible = 0.0;
           $montoExento = 0.0;
           $montoIva = 0.0;
           $detallesProcesados = [];
           foreach ($items as $item) {
               $producto = Producto::find((int)$item['producto_id']);
               $cant     = (float)$item['cantidad'];
               $precio   = (float)$item['precio_unitario'];
               $desc     = (float)($item['porcentaje_descuento'] ?? 0.0);
               
               $precioNeto = $precio * (1 - ($desc / 100));
               $stLinea    = $cant * $precioNeto;
               $porcIva    = (float)($producto['exento_iva'] ? 0.0 : $producto['porcentaje_iva']);
               $ivaLinea   = $stLinea * ($porcIva / 100);
               $subtotalNeto += $stLinea;
               if ($porcIva > 0) {
                   $baseImponible += $stLinea;
                   $montoIva += $ivaLinea;
               } else {
                   $montoExento += $stLinea;
               }
               $detallesProcesados[] = [
                   'producto_id'              => $producto['id'],
                   'cantidad'                 => $cant,
                   'costo_unitario_historico' => (float)$producto['costo_promedio'],
                   'precio_unitario'          => $precio,
                   'porcentaje_descuento'     => $desc,
                   'porcentaje_iva'           => $porcIva,
                   'subtotal'                 => round($stLinea, 4),
                   'total'                    => round($stLinea + $ivaLinea, 4)
               ];

           }
           $totalGeneral = $subtotalNeto + $montoIva;
           // 3. Insertar Cabecera
           $stmtVenta = $db->prepare("
               INSERT INTO ventas (
                   tipo_documento, numero_documento, control_fiscal, cliente_id, vendedor_id, 
                   deposito_id, usuario_id, documento_origen_id, documento_origen_tipo,
                   fecha_emision, fecha_vencimiento, condicion_pago, moneda_id, tasa_cambio, 
                   subtotal_neto, monto_exento, base_imponible, monto_iva, total_general, 
                   saldo_pendiente, estado, estado_flujo, nota
               ) VALUES (
                   :tipo_documento, :numero_documento, :control_fiscal, :cliente_id, :vendedor_id, 
                   :deposito_id, :usuario_id, :doc_origen_id, :doc_origen_tipo,
                   :fecha_emision, :fecha_vencimiento, :condicion_pago, :moneda_id, :tasa_cambio, 
                   :subtotal_neto, :monto_exento, :base_imponible, :monto_iva, :total_general, 
                   :saldo_pendiente, :estado, :estado_flujo, :nota
               )
           ");
            $generaCxC = in_array($tipoDoc, ['FACTURA', 'APARTADO']) || ($tipoDoc === 'NOTA_ENTREGA' && !empty($cabecera['genera_cxc']));
            $saldoInicial = ($generaCxC && ($cabecera['condicion_pago'] ?? 'CONTADO') === 'CREDITO') ? $totalGeneral : 0.0;
           $stmtVenta->execute([
               'tipo_documento'     => $tipoDoc,
               'numero_documento'   => $cabecera['numero_documento'],
               'control_fiscal'     => $cabecera['control_fiscal'] ?? null,
               'cliente_id'         => $clienteId,
               'vendedor_id'        => (int)$cabecera['vendedor_id'],
               'deposito_id'        => $depositoId,
               'usuario_id'         => $usuarioId,
               'doc_origen_id'      => $docOrigenId,
               'doc_origen_tipo'    => $cabecera['documento_origen_tipo'] ?? null,
               'fecha_emision'      => $cabecera['fecha_emision'] ?? date('Y-m-d'),
               'fecha_vencimiento'  => $cabecera['fecha_vencimiento'] ?? date('Y-m-d'),
               'condicion_pago'     => $cabecera['condicion_pago'] ?? 'CONTADO',
               'moneda_id'          => (int)($cabecera['moneda_id'] ?? 1),
               'tasa_cambio'        => (float)($cabecera['tasa_cambio'] ?? 1.0),
               'subtotal_neto'      => round($subtotalNeto, 4),
               'monto_exento'       => round($montoExento, 4),
               'base_imponible'     => round($baseImponible, 4),
               'monto_iva'          => round($montoIva, 4),
               'total_general'      => round($totalGeneral, 4),
               'saldo_pendiente'    => round($saldoInicial, 4),
               'estado'             => ($saldoInicial > 0) ? 'PENDIENTE' : 'PAGADA',
               'estado_flujo'       => 'PENDIENTE',
               'nota'               => $cabecera['nota'] ?? null
           ]);
           $nuevoDocId = (int)$db->lastInsertId();
           // 4. Insertar Renglones y Gestionar Inventario según Tipo
           $stmtDet = $db->prepare("
               INSERT INTO ventas_detalles (
                   venta_id, producto_id, cantidad, costo_unitario_historico, 
                   precio_unitario, porcentaje_descuento, porcentaje_iva, subtotal, total
               ) VALUES (
                   :venta_id, :producto_id, :cantidad, :costo_unitario_historico, 
                   :precio_unitario, :porcentaje_descuento, :porcentaje_iva, :subtotal, :total
               )
           ");
           foreach ($detallesProcesados as $det) {
               $det['venta_id'] = $nuevoDocId;
               $stmtDet->execute($det);
               // ACCIÓN A: Comprometer Stock (Pedidos / Apartados)
               if (in_array($tipoDoc, ['PEDIDO', 'APARTADO'])) {
                   $db->prepare("
                       UPDATE producto_deposito 
                       SET existencia_comprometida = existencia_comprometida + :cant 
                       WHERE producto_id = :p AND deposito_id = :d
                   ")->execute(['cant' => $det['cantidad'], 'p' => $det['producto_id'], 'd' => $depositoId]);

               }
               // ACCIÓN B: Rebajar Físicamente (Facturas / Notas de Entrega)
               elseif (in_array($tipoDoc, ['FACTURA', 'NOTA_ENTREGA'])) {
                   // Si viene de un Pedido/Apartado previo, liberar lo comprometido
                   if (!empty($docOrigenId) && in_array($cabecera['documento_origen_tipo'], ['PEDIDO', 'APARTADO'])) {
                       $db->prepare("
                           UPDATE producto_deposito 
                           SET existencia_comprometida = GREATEST(0, existencia_comprometida - :cant) 
                           WHERE producto_id = :p AND deposito_id = :d
                       ")->execute(['cant' => $det['cantidad'], 'p' => $det['producto_id'], 'd' => $depositoId]);
                   }
                   // Salida real del almacén y asiento en Kardex
                   InventarioService::registrarMovimiento(
                       $det['producto_id'],
                       $depositoId,
                       'SALIDA_VENTA',
                       $tipoDoc,
                       $cabecera['numero_documento'],
                       $det['cantidad'],
                       $det['costo_unitario_historico'],
                       $usuarioId,
                       "Emisión de {$tipoDoc}"
                   ); //
               }
               // ACCIÓN C: Reingresar Stock (Devoluciones / Notas de Crédito)
               elseif ($tipoDoc === 'DEVOLUCION_VENTA') {
                   InventarioService::registrarMovimiento(
                       $det['producto_id'],
                       $depositoId,
                       'AJUSTE_POSITIVO',
                       'DEV_VENTA',
                       $cabecera['numero_documento'],
                       $det['cantidad'],
                       $det['costo_unitario_historico'],
                       $usuarioId,
                       "Devolución s/ Factura Origen ID: {$docOrigenId}"
                   ); //
               }
           }
           // 5. Si proviene de otro documento, marcar estado del documento origen
           if (!empty($docOrigenId)) {
               $db->prepare("UPDATE ventas SET estado_flujo = 'PROCESADO_TOTAL' WHERE id = :id")->execute(['id' => $docOrigenId]);
           }
           // 6. Cuentas por Cobrar (si aplica crédito o devolución)
           if ($tipoDoc === 'DEVOLUCION_VENTA') {
               Cliente::actualizarSaldo($clienteId, $totalGeneral, 'ABONO');
           } elseif ($saldoInicial > 0) {
               Cliente::actualizarSaldo($clienteId, $totalGeneral, 'CARGO');
           }
           Database::commit();
           return [
               'status'       => 'success',
               'documento_id' => $nuevoDocId,
               'venta_id'     => $nuevoDocId,
               'tipo'         => $tipoDoc,
               'total'        => $totalGeneral
           ];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
}