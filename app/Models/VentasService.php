<?php
namespace App\Models;
use App\Core\Database;
use App\Models\InventarioService;
use App\Models\Cliente;
use Exception;
class VentasService {
   public static function procesarVenta(array $cabecera, array $items): array {
       $db = Database::getConnection();
       
       try {
           Database::beginTransaction();
           $clienteId   = (int)$cabecera['cliente_id'];
           $depositoId  = (int)$cabecera['deposito_id'];
           $tipoDoc     = $cabecera['tipo_documento']; // FACTURA, NOTA_ENTREGA, etc.
           $afectaStock = in_array($tipoDoc, ['FACTURA', 'NOTA_ENTREGA']);
           // 1. Calcular totales del documento
           $subtotalNeto  = 0.0;
           $montoExento   = 0.0;
           $baseImponible = 0.0;
           $montoIva      = 0.0;
           $detallesProcesados = [];
           foreach ($items as $item) {
               $producto = Producto::find((int)$item['producto_id']);
               if (!$producto) {
                   throw new Exception("Producto ID {$item['producto_id']} no encontrado.");
               }
               $cantidad   = (float)$item['cantidad'];
               $precioUnit = (float)$item['precio_unitario'];
               $porcDesc   = (float)($item['porcentaje_descuento'] ?? 0.0);
               
               $precioFinal = $precioUnit * (1 - ($porcDesc / 100));
               $subtotalLin = $cantidad * $precioFinal;
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
               $detallesProcesados[] = [
                   'producto_id'              => $producto['id'],
                   'cantidad'                 => $cantidad,
                   'costo_unitario_historico' => (float)$producto['costo_promedio'],
                   'precio_unitario'          => $precioUnit,
                   'porcentaje_descuento'     => $porcDesc,
                   'porcentaje_iva'           => $porcIva,
                   'subtotal'                 => round($subtotalLin, 4),
                   'total'                    => round($totalLin, 4),
                   'seriales'                 => array_map('strval', (array)($item['seriales'] ?? []))
               ];
           }
           $totalGeneral = $subtotalNeto + $montoIva;
           // 2. Si es a crédito, validar límite del cliente
           if ($cabecera['condicion_pago'] === 'CREDITO') {
               Cliente::validarCredito($clienteId, $totalGeneral);
           }
           // 3. Insertar Cabecera de Venta
           $stmt = $db->prepare("
               INSERT INTO ventas (
                   tipo_documento, numero_documento, control_fiscal, cliente_id, vendedor_id, 
                   deposito_id, usuario_id, fecha_emision, fecha_vencimiento, condicion_pago, 
                   moneda_id, tasa_cambio, subtotal_neto, monto_exento, base_imponible, 
                   monto_iva, total_general, saldo_pendiente, estado, nota
               ) VALUES (
                   :tipo_documento, :numero_documento, :control_fiscal, :cliente_id, :vendedor_id, 
                   :deposito_id, :usuario_id, :fecha_emision, :fecha_vencimiento, :condicion_pago, 
                   :moneda_id, :tasa_cambio, :subtotal_neto, :monto_exento, :base_imponible, 
                   :monto_iva, :total_general, :saldo_pendiente, :estado, :nota
               )
           ");
           $saldoPendiente = ($cabecera['condicion_pago'] === 'CREDITO') ? $totalGeneral : 0.0;
           $estadoVenta    = ($cabecera['condicion_pago'] === 'CREDITO') ? 'PENDIENTE' : 'PAGADA';
           $stmt->execute([
               'tipo_documento'   => $tipoDoc,
               'numero_documento' => $cabecera['numero_documento'],
               'control_fiscal'   => $cabecera['control_fiscal'] ?? null,
               'cliente_id'       => $clienteId,
               'vendedor_id'      => (int)($cabecera['vendedor_id'] ?? 1),
               'deposito_id'      => $depositoId,
               'usuario_id'       => (int)($cabecera['usuario_id'] ?? 1),
               'fecha_emision'    => $cabecera['fecha_emision'] ?? date('Y-m-d'),
               'fecha_vencimiento'=> $cabecera['fecha_vencimiento'] ?? date('Y-m-d'),
               'condicion_pago'   => $cabecera['condicion_pago'],
               'moneda_id'        => (int)($cabecera['moneda_id'] ?? 1),
               'tasa_cambio'      => (float)($cabecera['tasa_cambio'] ?? 1.0),
               'subtotal_neto'    => round($subtotalNeto, 4),
               'monto_exento'     => round($montoExento, 4),
               'base_imponible'   => round($baseImponible, 4),
               'monto_iva'        => round($montoIva, 4),
               'total_general'    => round($totalGeneral, 4),
               'saldo_pendiente'  => round($saldoPendiente, 4),
               'estado'           => $estadoVenta,
               'nota'             => $cabecera['nota'] ?? null
           ]);
           $ventaId = (int)$db->lastInsertId();
           // 4. Insertar Detalles y Descontar Stock
           $stmtDetalle = $db->prepare("
               INSERT INTO ventas_detalles (
                   venta_id, producto_id, cantidad, costo_unitario_historico, 
                   precio_unitario, porcentaje_descuento, porcentaje_iva, subtotal, total
               ) VALUES (
                   :venta_id, :producto_id, :cantidad, :costo_unitario_historico, 
                   :precio_unitario, :porcentaje_descuento, :porcentaje_iva, :subtotal, :total
               )
           ");

           foreach ($detallesProcesados as $det) {
               $det['venta_id'] = $ventaId;
               $stmtDetalle->execute($det);
               $ventaDetalleId = (int)$db->lastInsertId();
               // Rebajar inventario y registrar Kardex si aplica
               if ($afectaStock) {
                   InventarioService::registrarMovimiento(
                       $det['producto_id'],
                       $depositoId,
                       'SALIDA_VENTA',
                       $tipoDoc,
                       $cabecera['numero_documento'],
                       $det['cantidad'],
                       $det['costo_unitario_historico'],
                       (int)($cabecera['usuario_id'] ?? 1),
                       "Venta Doc: {$cabecera['numero_documento']}"
                   );
                   // Trazabilidad: despachar seriales/IMEI asignados al renglón
                   if (!empty($det['seriales'])) {
                       SerialesService::despacharSerialesVenta(
                           $ventaId,
                           $ventaDetalleId,
                           (int)$det['producto_id'],
                           $depositoId,
                           $det['seriales'],
                           $clienteId
                       );
                   }
               }
           }
            // 5. Asentar en Cuentas por Cobrar si es a Crédito, o Registrar Cobro de Contado si es Contado
            if ($cabecera['condicion_pago'] === 'CREDITO') {
                $stmtCxC = $db->prepare("
                    INSERT INTO cuentas_por_cobrar (
                        venta_id, cliente_id, tipo_transaccion, numero_referencia, 
                        monto, fecha, usuario_id, nota
                    ) VALUES (
                        :venta_id, :cliente_id, 'CARGO_FACTURA', :numero_referencia, 
                        :monto, :fecha, :usuario_id, :nota
                    )
                ");
                $stmtCxC->execute([
                    'venta_id'          => $ventaId,
                    'cliente_id'        => $clienteId,
                    'numero_referencia' => $cabecera['numero_documento'],
                    'monto'             => round($totalGeneral, 4),
                    'fecha'             => $cabecera['fecha_emision'] ?? date('Y-m-d'),
                    'usuario_id'        => (int)($cabecera['usuario_id'] ?? 1),
                    'nota'              => "Emisión de {$tipoDoc} a crédito"
                ]);
                Cliente::actualizarSaldo($clienteId, $totalGeneral, 'CARGO');
            } else {
                // Cobro Contado: Generar Recibo de Cobranza + Formas de Pago + Movimiento Bancario de Caja
                $numRecibo = 'REC-' . date('ymd') . '-' . rand(1000, 9999);
                $tasaCambio = (float)($cabecera['tasa_cambio'] ?? 1.0);

                $stmtRecibo = $db->prepare("
                    INSERT INTO recibos_cobranza (
                        numero_recibo, cliente_id, fecha, moneda_id, tasa_cambio, monto_total, usuario_id, nota
                    ) VALUES (
                        :num, :cli, :fecha, :moneda_id, :tasa, :monto_total, :usr, :nota
                    )
                ");
                $stmtRecibo->execute([
                    'num'         => $numRecibo,
                    'cli'         => $clienteId,
                    'fecha'       => $cabecera['fecha_emision'] ?? date('Y-m-d'),
                    'moneda_id'   => (int)($cabecera['moneda_id'] ?? 1),
                    'tasa'        => $tasaCambio,
                    'monto_total' => round($totalGeneral, 4),
                    'usr'         => (int)($cabecera['usuario_id'] ?? 1),
                    'nota'        => "Cobro POS al contado Venta: {$cabecera['numero_documento']}"
                ]);
                $reciboId = (int)$db->lastInsertId();

                $stmtRecDet = $db->prepare("
                    INSERT INTO recibos_cobranza_detalles (recibo_id, venta_id, monto_abonado)
                    VALUES (:recibo_id, :venta_id, :monto)
                ");
                $stmtRecDet->execute([
                    'recibo_id' => $reciboId,
                    'venta_id'  => $ventaId,
                    'monto'     => round($totalGeneral, 4)
                ]);

                $formasPago = $cabecera['formas_pago'] ?? [
                    [
                        'forma_pago' => 'EFECTIVO',
                        'monto'      => round($totalGeneral, 4),
                        'cuenta_id'  => 1,
                        'referencia' => $cabecera['numero_documento']
                    ]
                ];

                $stmtForma = $db->prepare("
                    INSERT INTO recibos_cobranza_formas_pago (
                        recibo_id, cuenta_id, forma_pago, referencia, monto
                    ) VALUES (
                        :recibo_id, :cuenta_id, :forma_pago, :ref, :monto
                    )
                ");

                $stmtMovBancario = $db->prepare("
                    INSERT INTO movimientos_bancarios (
                        cuenta_id, tipo_movimiento, numero_referencia, monto, tasa_cambio, fecha, conciliado, beneficiario_concepto, usuario_id
                    ) VALUES (
                        :cuenta_id, 'COBRO_CLIENTE', :ref, :monto, :tasa, :fecha, 1, :concepto, :usr
                    )
                ");

                foreach ($formasPago as $fp) {
                    $forma = $fp['forma_pago'] ?? 'EFECTIVO';
                    $montoFp = (float)($fp['monto'] ?? $totalGeneral);
                    $cuentaId = (int)($fp['cuenta_id'] ?? 1);
                    $ref = $fp['referencia'] ?? $cabecera['numero_documento'];

                    $stmtForma->execute([
                        'recibo_id'  => $reciboId,
                        'cuenta_id'  => $cuentaId,
                        'forma_pago' => $forma,
                        'ref'        => $ref,
                        'monto'      => round($montoFp, 4)
                    ]);

                    $stmtMovBancario->execute([
                        'cuenta_id'  => $cuentaId,
                        'ref'        => $ref,
                        'monto'      => round($montoFp, 4),
                        'tasa'       => $tasaCambio,
                        'fecha'      => $cabecera['fecha_emision'] ?? date('Y-m-d'),
                        'concepto'   => "Cobro POS {$cabecera['numero_documento']} ({$forma})",
                        'usr'        => (int)($cabecera['usuario_id'] ?? 1)
                    ]);

                    $db->exec("UPDATE cuentas_bancarias SET saldo_actual = saldo_actual + {$montoFp} WHERE id = {$cuentaId}");
                }
            }
           Database::commit();
           return [
               'status'   => 'success',
               'venta_id' => $ventaId,
               'total'    => $totalGeneral
           ];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
}