<?php
namespace App\Core;
use App\Core\Database;
use PDO;
use Exception;
class ContabilidadEngine {
   /**
    * Resuelve el ID de cuenta contable asociado a un concepto de enlace
    */
   private static function getCuentaId(string $concepto): int {
       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT cuenta_id FROM contabilidad_mapeo_enlace WHERE concepto = :c LIMIT 1");
       $stmt->execute(['c' => $concepto]);
       $cuentaId = $stmt->fetchColumn();
       if (!$cuentaId) {
           throw new Exception("Configuración contable incompleta: Concepto '{$concepto}' no tiene cuenta asignada.");
       }
       return (int)$cuentaId;
   }
   /**
    * 1. ASIENTO AUTOMÁTICO DE VENTA Y RECONOCIMIENTO DE COSTO DE VENTAS
    */
   public static function contabilizarVenta(int $ventaId): void {
       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT * FROM ventas WHERE id = :id FOR UPDATE");
       $stmt->execute(['id' => $ventaId]);
       $v = $stmt->fetch();
       if (!$v || $v['estado'] === 'ANULADA') return;
       // Calcular costo de ventas histórico de los renglones
       $stmtCostos = $db->prepare("SELECT SUM(cantidad * costo_unitario_historico) AS costo_total FROM ventas_detalles WHERE venta_id = :id");
       $stmtCostos->execute(['id' => $ventaId]);
       $costoTotalVenta = (float)$stmtCostos->fetchColumn();
       // 1.1 Asiento de Facturación
       $asientosFactura = [];
       $totalDoc = (float)$v['total_general'];
       $baseImp  = (float)$v['base_imponible'] + (float)$v['monto_exento'];
       $montoIva = (float)$v['monto_iva'];
       // DEBE: Caja (si es Contado) o CxC (si es Crédito)
       $cuentaCobro = ($v['condicion_pago'] === 'CONTADO') ? self::getCuentaId('CAJA_EFECTIVO') : self::getCuentaId('CXC_CLIENTES');
       $asientosFactura[] = [
           'cuenta_id' => $cuentaCobro,
           'desc'      => "Registro Venta {$v['tipo_documento']} {$v['numero_documento']}",
           'debe'      => $totalDoc,
           'haber'     => 0.0
       ];
       // HABER: Ingresos por Ventas
       $asientosFactura[] = [
           'cuenta_id' => self::getCuentaId('VENTAS_INGRESOS'),
           'desc'      => "Ingreso por Venta {$v['numero_documento']}",
           'debe'      => 0.0,
           'haber'     => $baseImp
       ];
       // HABER: Débito Fiscal IVA
       if ($montoIva > 0) {
           $asientosFactura[] = [
               'cuenta_id' => self::getCuentaId('IVA_DEBITO'),
               'desc'      => "Débito Fiscal IVA Factura {$v['numero_documento']}",
               'debe'      => 0.0,
               'haber'     => $montoIva
           ];
       }

       self::registrarComprobante(
           "COMP-VT-{$v['id']}",
           $v['fecha_emision'],
           'VENTA',
           $v['id'],
           "Contabilización de {$v['tipo_documento']} {$v['numero_documento']}",
           $asientosFactura,
           $v['usuario_id'],
           $v['moneda_id'],
           (float)$v['tasa_cambio']
       );
       // 1.2 Asiento de Costo de Ventas (Descarga de Inventario)
       if ($costoTotalVenta > 0) {
           $asientosCosto = [
               [
                   'cuenta_id' => self::getCuentaId('COSTO_VENTAS'),
                   'desc'      => "Costo de Ventas Factura {$v['numero_documento']}",
                   'debe'      => $costoTotalVenta,
                   'haber'     => 0.0
               ],
               [
                   'cuenta_id' => self::getCuentaId('INVENTARIO_MERCANCIA'),
                   'desc'      => "Descarga de Inventario por Venta {$v['numero_documento']}",
                   'debe'      => 0.0,
                   'haber'     => $costoTotalVenta
               ]
           ];
           self::registrarComprobante(
               "COMP-CV-{$v['id']}",
               $v['fecha_emision'],
               'COSTO_VENTA',
               $v['id'],
               "Costo de Venta y Salida de Inventario {$v['numero_documento']}",
               $asientosCosto,
               $v['usuario_id'],
               $v['moneda_id'],
               (float)$v['tasa_cambio']
           );
       }
   }
   /**
    * 2. ASIENTO AUTOMÁTICO DE COMPRA Y RETENCIONES FISCALES
    */
   public static function contabilizarCompra(int $compraId): void {
       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT * FROM compras WHERE id = :id FOR UPDATE");
       $stmt->execute(['id' => $compraId]);
       $c = $stmt->fetch();
       if (!$c || $c['estado'] === 'ANULADA') return;
       $asientos = [];
       $baseImp  = (float)$c['base_imponible'] + (float)$c['monto_exento'];
       $montoIva = (float)$c['monto_iva'];
       $retIva   = (float)$c['retencion_iva_monto'];
       $retIslr  = (float)$c['retencion_islr_monto'];
       $cxpNeto  = (float)$c['total_a_pagar'];
       // DEBE: Inventario (Entrada de Mercancía a Costo)
       $asientos[] = [
           'cuenta_id' => self::getCuentaId('INVENTARIO_MERCANCIA'),
           'desc'      => "Carga de Inventario por Compra {$c['numero_factura']}",
           'debe'      => $baseImp,
           'haber'     => 0.0
       ];
       // DEBE: Crédito Fiscal IVA
       if ($montoIva > 0) {
           $asientos[] = [
               'cuenta_id' => self::getCuentaId('IVA_CREDITO'),

               'desc'      => "Crédito Fiscal IVA Factura Proveedor {$c['numero_factura']}",
               'debe'      => $montoIva,
               'haber'     => 0.0
           ];
       }
       // HABER: Retención IVA por Enterar
       if ($retIva > 0) {
           $asientos[] = [
               'cuenta_id' => self::getCuentaId('RET_IVA_POR_PAGAR'),
               'desc'      => "Retención IVA Compra {$c['numero_factura']}",
               'debe'      => 0.0,
               'haber'     => $retIva
           ];
       }
       // HABER: Retención ISLR por Enterar
       if ($retIslr > 0) {
           $asientos[] = [
               'cuenta_id' => self::getCuentaId('RET_ISLR_POR_PAGAR'),
               'desc'      => "Retención ISLR Compra {$c['numero_factura']}",
               'debe'      => 0.0,
               'haber'     => $retIslr
           ];
       }
       // HABER: Cuenta por Pagar Proveedores (Monto Neto a Liquidar)
       $asientos[] = [
           'cuenta_id' => self::getCuentaId('CXP_PROVEEDORES'),
           'desc'      => "Pasivo Proveedor {$c['numero_factura']}",
           'debe'      => 0.0,
           'haber'     => $cxpNeto
       ];
       self::registrarComprobante(
           "COMP-CP-{$c['id']}",
           $c['fecha_recepcion'],
           'COMPRA',
           $c['id'],
           "Contabilización Compra Factura {$c['numero_factura']}",
           $asientos,
           $c['usuario_id'],
           $c['moneda_id'],
           (float)$c['tasa_cambio']
       );
   }
   /**
    * 3. ASIENTO DE COBRO MULTIPAGO DE CLIENTE (CxC)
    */
   public static function contabilizarCobro(int $reciboId): void {
       $db = Database::getConnection();
       $stmt = $db->prepare("SELECT * FROM recibos_cobranza WHERE id = :id");
       $stmt->execute(['id' => $reciboId]);
       $rc = $stmt->fetch();
       if (!$rc) return;
       // Formas de pago recibidas
       $stmtFp = $db->prepare("SELECT * FROM recibos_cobranza_formas_pago WHERE recibo_id = :id");
       $stmtFp->execute(['id' => $reciboId]);
       $formasPago = $stmtFp->fetchAll();
       $asientos = [];
       $totalCobrado = (float)$rc['monto_total'];
       // DEBE: Caja o Bancos según cada instrumento recibido
       foreach ($formasPago as $fp) {
           $monto = (float)$fp['monto'];
           $cuentaDestino = in_array($fp['forma_pago'], ['EFECTIVO']) ? self::getCuentaId('CAJA_EFECTIVO') : self::getCuentaId('BANCOS');
           $asientos[] = [
               'cuenta_id' => $cuentaDestino,
               'desc'      => "Ingreso Cobro {$fp['forma_pago']} Ref: {$fp['referencia']}",

               'debe'      => $monto,
               'haber'     => 0.0
           ];
       }
       // HABER: Disminución de Cuentas por Cobrar Comerciales
       $asientos[] = [
           'cuenta_id' => self::getCuentaId('CXC_CLIENTES'),
           'desc'      => "Liquidación CxC Recibo N° {$rc['numero_recibo']}",
           'debe'      => 0.0,
           'haber'     => $totalCobrado
       ];
       self::registrarComprobante(
           "COMP-RC-{$rc['id']}",
           $rc['fecha'],
           'COBRO_CXC',
           $rc['id'],
           "Recibo de Cobranza N° {$rc['numero_recibo']}",
           $asientos,
           $rc['usuario_id'],
           $rc['moneda_id'],
           (float)$rc['tasa_cambio']
       );
   }
   /**
    * Inserta el comprobante y valida la partida doble
    */
   private static function registrarComprobante(
       string $numeroComprobante,
       string $fecha,
       string $tipoOrigen,
       int $documentoOrigenId,
       string $conceptoGeneral,
       array $asientos,
       int $usuarioId,
       int $monedaId,
       float $tasaCambio
   ): int {
       $db = Database::getConnection();
       $totalDebe = 0.0;
       $totalHaber = 0.0;
       foreach ($asientos as $a) {
           $totalDebe  += (float)$a['debe'];
           $totalHaber += (float)$a['haber'];
       }
       // Verificación de Partida Doble
       if (abs($totalDebe - $totalHaber) > 0.001) {
           throw new Exception("Error en asiento contable: Comprobante descuadrado (Debe: {$totalDebe} vs Haber: {$totalHaber}).");
       }
       $stmtComp = $db->prepare("
           INSERT INTO contabilidad_comprobantes 
           (numero_comprobante, fecha, tipo_origen, documento_origen_id, concepto_general, total_debe, total_haber, moneda_id, tasa_cambio, estado, usuario_id)
           VALUES (:num, :fec, :tipo, :doc_id, :conc, :debe, :haber, :mon, :tasa, 'ASENTADO', :usr)
           ON DUPLICATE KEY UPDATE total_debe = VALUES(total_debe), total_haber = VALUES(total_haber)
       ");
       $stmtComp->execute([
           'num'    => $numeroComprobante,
           'fec'    => $fecha,
           'tipo'   => $tipoOrigen,
           'doc_id' => $documentoOrigenId,
           'conc'   => $conceptoGeneral,
           'debe'   => $totalDebe,
           'haber'  => $totalHaber,
           'mon'    => $monedaId,
           'tasa'   => $tasaCambio,
           'usr'    => $usuarioId
       ]);

       $comprobanteId = (int)$db->lastInsertId();
       if (!$comprobanteId) {
           $stmtGet = $db->prepare("SELECT id FROM contabilidad_comprobantes WHERE numero_comprobante = :num");
           $stmtGet->execute(['num' => $numeroComprobante]);
           $comprobanteId = (int)$stmtGet->fetchColumn();
           $db->prepare("DELETE FROM contabilidad_asientos_detalles WHERE comprobante_id = :id")->execute(['id' => $comprobanteId]);
       }
       $stmtDet = $db->prepare("
           INSERT INTO contabilidad_asientos_detalles 
           (comprobante_id, cuenta_id, descripcion_renglon, debe, haber)
           VALUES (:comp, :cta, :desc, :debe, :haber)
       ");
       foreach ($asientos as $a) {
           $stmtDet->execute([
               'comp'  => $comprobanteId,
               'cta'   => $a['cuenta_id'],
               'desc'  => $a['desc'],
               'debe'  => $a['debe'],
               'haber' => $a['haber']
           ]);
       }
       return $comprobanteId;
   }
}