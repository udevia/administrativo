<?php
namespace App\Models;
use App\Core\Database;
use App\Models\InventarioService;
use App\Models\Proveedor;
use Exception;
class ComprasService {
   public static function procesarCompra(array $cabecera, array $items): array {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           $proveedorId = (int)$cabecera['proveedor_id'];
           $depositoId  = (int)$cabecera['deposito_id'];
           $tipoDoc     = $cabecera['tipo_documento'];
           $afectaStock = in_array($tipoDoc, ['FACTURA_COMPRA', 'NOTA_RECEPCION']);
           $proveedor = Proveedor::find($proveedorId);
           if (!$proveedor) {
               throw new Exception("El proveedor especificado no existe.");
           }
           // 1. Cálculo de renglones
           $subtotalNeto  = 0.0;
           $montoExento   = 0.0;
           $baseImponible = 0.0;

           $montoIva      = 0.0;
           $detalles      = [];
           foreach ($items as $item) {
               $producto = Producto::find((int)$item['producto_id']);
               if (!$producto) {
                   throw new Exception("Producto ID {$item['producto_id']} inválido.");
               }
               $cantidad   = (float)$item['cantidad'];
               $costoUnit  = (float)$item['costo_unitario'];
               $porcDesc   = (float)($item['porcentaje_descuento'] ?? 0.0);
               $costoEfectivo = $costoUnit * (1 - ($porcDesc / 100));
               $subtotalLin   = $cantidad * $costoEfectivo;
               $porcIva = (float)($producto['exento_iva'] ? 0.0 : $producto['porcentaje_iva']);
               $ivaLin  = $subtotalLin * ($porcIva / 100);
               $totalLin = $subtotalLin + $ivaLin;
               $subtotalNeto += $subtotalLin;
               if ($porcIva > 0) {
                   $baseImponible += $subtotalLin;
                   $montoIva += $ivaLin;
               } else {
                   $montoExento += $subtotalLin;
               }
               $detalles[] = [
                   'producto_id'          => $producto['id'],
                   'cantidad'             => $cantidad,
                   'costo_unitario'       => $costoUnit,
                   'porcentaje_descuento' => $porcDesc,
                   'porcentaje_iva'       => $porcIva,
                   'subtotal'             => round($subtotalLin, 4),
                   'total'                => round($totalLin, 4)
               ];
           }
           $totalGeneral = $subtotalNeto + $montoIva;
           // 2. Cálculos de Retenciones Fiscales
           $retIvaMonto = 0.0;
           $porcRetIva  = (float)($cabecera['porcentaje_retencion_iva'] ?? 0.0);
           if ($porcRetIva > 0 && $montoIva > 0) {
               $retIvaMonto = $montoIva * ($porcRetIva / 100);
           }
           $retIslrMonto = 0.0;
           $porcRetIslr  = (float)($cabecera['porcentaje_retencion_islr'] ?? 0.0);
           if ($porcRetIslr > 0 && $baseImponible > 0) {
               $retIslrMonto = $baseImponible * ($porcRetIslr / 100);
           }
           $totalAPagar = $totalGeneral - $retIvaMonto - $retIslrMonto;
           $saldoPendiente = ($cabecera['condicion_pago'] === 'CREDITO') ? $totalAPagar : 0.0;
           $estadoCompra   = ($cabecera['condicion_pago'] === 'CREDITO') ? 'PENDIENTE' : 'PAGADA';
           // 3. Insertar Cabecera de Compra
           $stmt = $db->prepare("
               INSERT INTO compras (
                   tipo_documento, numero_factura, numero_control, proveedor_id, deposito_id, 
                   usuario_id, fecha_emision, fecha_recepcion, fecha_vencimiento, condicion_pago, 
                   moneda_id, tasa_cambio, subtotal_neto, monto_exento, base_imponible, 
                   monto_iva, retencion_iva_monto, retencion_islr_monto, total_general, 
                   total_a_pagar, saldo_pendiente, estado, nota
               ) VALUES (
                   :tipo_documento, :numero_factura, :numero_control, :proveedor_id, :deposito_id, 
                   :usuario_id, :fecha_emision, :fecha_recepcion, :fecha_vencimiento, :condicion_pago, 
                   :moneda_id, :tasa_cambio, :subtotal_neto, :monto_exento, :base_imponible, 
                   :monto_iva, :retencion_iva_monto, :retencion_islr_monto, :total_general, 
                   :total_a_pagar, :saldo_pendiente, :estado, :nota
               )
           ");

           $stmt->execute([
               'tipo_documento'       => $tipoDoc,
               'numero_factura'       => $cabecera['numero_factura'],
               'numero_control'       => $cabecera['numero_control'] ?? null,
               'proveedor_id'         => $proveedorId,
               'deposito_id'          => $depositoId,
               'usuario_id'           => (int)($cabecera['usuario_id'] ?? 1),
               'fecha_emision'        => $cabecera['fecha_emision'] ?? date('Y-m-d'),
               'fecha_recepcion'      => $cabecera['fecha_recepcion'] ?? date('Y-m-d'),
               'fecha_vencimiento'    => $cabecera['fecha_vencimiento'] ?? date('Y-m-d'),
               'condicion_pago'       => $cabecera['condicion_pago'],
               'moneda_id'            => (int)($cabecera['moneda_id'] ?? 1),
               'tasa_cambio'          => (float)($cabecera['tasa_cambio'] ?? 1.0),
               'subtotal_neto'        => round($subtotalNeto, 4),
               'monto_exento'         => round($montoExento, 4),
               'base_imponible'       => round($baseImponible, 4),
               'monto_iva'            => round($montoIva, 4),
               'retencion_iva_monto'  => round($retIvaMonto, 4),
               'retencion_islr_monto' => round($retIslrMonto, 4),
               'total_general'        => round($totalGeneral, 4),
               'total_a_pagar'        => round($totalAPagar, 4),
               'saldo_pendiente'      => round($saldoPendiente, 4),
               'estado'               => $estadoCompra,
               'nota'                 => $cabecera['nota'] ?? null
           ]);
           $compraId = (int)$db->lastInsertId();
           // 4. Insertar Renglones y Cargar Inventario
           $stmtDet = $db->prepare("
               INSERT INTO compras_detalles (
                   compra_id, producto_id, cantidad, costo_unitario, 
                   porcentaje_descuento, porcentaje_iva, subtotal, total
               ) VALUES (
                   :compra_id, :producto_id, :cantidad, :costo_unitario, 
                   :porcentaje_descuento, :porcentaje_iva, :subtotal, :total
               )
           ");
           foreach ($detalles as $d) {
               $d['compra_id'] = $compraId;
               $stmtDet->execute($d);
               if ($afectaStock) {
                   InventarioService::registrarMovimiento(
                       $d['producto_id'],
                       $depositoId,
                       'ENTRADA_COMPRA',
                       $tipoDoc,
                       $cabecera['numero_factura'],
                       $d['cantidad'],
                       $d['costo_unitario'],
                       (int)($cabecera['usuario_id'] ?? 1),
                       "Recepción Factura Proveedor: {$cabecera['numero_factura']}"
                   ); // Actualiza stock y recalcula costo promedio ponderado automáticamente
               }
           }
           // 5. Asentar en Cuentas por Pagar si es a Crédito
           if ($cabecera['condicion_pago'] === 'CREDITO') {
               $stmtCxP = $db->prepare("
                   INSERT INTO cuentas_por_pagar (
                       compra_id, proveedor_id, tipo_transaccion, numero_referencia, 
                       monto, fecha, usuario_id, nota
                   ) VALUES (
                       :compra_id, :proveedor_id, 'CARGO_FACTURA', :numero_referencia, 
                       :monto, :fecha, :usuario_id, :nota
                   )
               ");
               $stmtCxP->execute([
                   'compra_id'         => $compraId,
                   'proveedor_id'      => $proveedorId,

                   'numero_referencia' => $cabecera['numero_factura'],
                   'monto'             => $totalAPagar,
                   'fecha'             => $cabecera['fecha_emision'] ?? date('Y-m-d'),
                   'usuario_id'        => (int)($cabecera['usuario_id'] ?? 1),
                   'nota'              => "Compra Doc: {$cabecera['numero_factura']}"
               ]);
               Proveedor::actualizarSaldo($proveedorId, $totalAPagar, 'CARGO');
           }
           Database::commit();
           return [
               'status'    => 'success',
               'compra_id' => $compraId,
               'total'     => $totalAPagar
           ];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
}