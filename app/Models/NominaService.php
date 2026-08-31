<?php
namespace App\Models;
use App\Core\Database;
use App\Core\ContabilidadEngine;
use PDO;
use Exception;
class NominaService {
   /**

    * 1. PRENÓMINA: Cálculo quincenal automático de asignaciones, deducciones y aportes
    */
    public static function calcularPrenomina(string $codigoPeriodo, string $fechaInicio, string $fechaFin, string $fechaPago, string $frecuencia, int $usuarioId): int {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           // Insertar o resetear periodo
           $stmtPer = $db->prepare("
               INSERT INTO nomina_periodos (codigo_periodo, frecuencia, fecha_inicio, fecha_fin, fecha_pago, estado, usuario_id)
               VALUES (:cod, :frec, :f_ini, :f_fin, :f_pag, 'PRENOMINA', :usr)
               ON DUPLICATE KEY UPDATE fecha_inicio = VALUES(fecha_inicio), fecha_fin = VALUES(fecha_fin), fecha_pago = VALUES(fecha_pago)
           ");
           $stmtPer->execute([
               'cod'   => $codigoPeriodo,
               'frec'  => $frecuencia,
               'f_ini' => $fechaInicio,
               'f_fin' => $fechaFin,
               'f_pag' => $fechaPago,
               'usr'   => $usuarioId
           ]);
           $periodoId = (int)$db->lastInsertId();
           if (!$periodoId) {
               $stmtGet = $db->prepare("SELECT id FROM nomina_periodos WHERE codigo_periodo = :c");
               $stmtGet->execute(['c' => $codigoPeriodo]);
               $periodoId = (int)$stmtGet->fetchColumn();
               $db->prepare("DELETE FROM nomina_recibos WHERE periodo_id = :p")->execute(['p' => $periodoId]);
           }
           // Obtener empleados activos
           $stmtEmp = $db->prepare("SELECT * FROM nomina_empleados WHERE estado = 'ACTIVO' AND frecuencia_pago = :frec");
           $stmtEmp->execute(['frec' => $frecuencia]);
           $empleados = $stmtEmp->fetchAll();
           $factorFrecuencia = ($frecuencia === 'QUINCENAL') ? 0.5 : ($frecuencia === 'SEMANAL' ? 0.25 : 1.0);
           $totalAsignacionesPeriodo = 0.0;
           $totalDeduccionesPeriodo   = 0.0;
           $totalAportesPatronales   = 0.0;
           $totalNetoPeriodo         = 0.0;
           $stmtRecibo = $db->prepare("
               INSERT INTO nomina_recibos (periodo_id, empleado_id, sueldo_base_periodo, total_asignaciones, total_deducciones, neto_cobrar)
               VALUES (:pid, :eid, :sb, :asig, :ded, :neto)
           ");
           $stmtDet = $db->prepare("
               INSERT INTO nomina_recibos_detalles (recibo_id, concepto_id, descripcion, tipo, monto)
               VALUES (:rid, :cid, :desc, :tipo, :monto)
           ");
           foreach ($empleados as $emp) {
               $sueldoBasePeriodo = (float)$emp['sueldo_base_mensual'] * $factorFrecuencia;
               $bonoAlimPeriodo   = (float)$emp['bono_alimentacion_mensual'] * $factorFrecuencia;
               $asigEmpleado = 0.0;
               $dedEmpleado  = 0.0;
               $detalles = [];
               // 1. Sueldo Base
               $asigEmpleado += $sueldoBasePeriodo;
               $detalles[] = ['cid' => 1, 'desc' => 'Sueldo Básico Quincenal', 'tipo' => 'ASIGNACION', 'monto' => $sueldoBasePeriodo];
               // 2. Cestaticket / Bono Alimentación
               if ($bonoAlimPeriodo > 0) {
                   $asigEmpleado += $bonoAlimPeriodo;
                   $detalles[] = ['cid' => 2, 'desc' => 'Bono de Alimentación', 'tipo' => 'ASIGNACION', 'monto' => $bonoAlimPeriodo];
               }
               // 3. Deducciones de Ley (Calculadas sobre Sueldo Base)
               if ($emp['aplica_ivss']) {
                   $montoIvss = $sueldoBasePeriodo * 0.04; // 4% IVSS Empleado

                   $dedEmpleado += $montoIvss;
                   $detalles[] = ['cid' => 3, 'desc' => 'Retención IVSS (4%)', 'tipo' => 'DEDUCCION', 'monto' => $montoIvss];
                   $aporteIvssPatrono = $sueldoBasePeriodo * 0.10; // 10% Aporte Empresa
                   $totalAportesPatronales += $aporteIvssPatrono;
               }
               if ($emp['aplica_faov']) {
                   $montoFaov = $sueldoBasePeriodo * 0.01; // 1% FAOV Empleado
                   $dedEmpleado += $montoFaov;
                   $detalles[] = ['cid' => 4, 'desc' => 'Retención FAOV (1%)', 'tipo' => 'DEDUCCION', 'monto' => $montoFaov];
                   $aporteFaovPatrono = $sueldoBasePeriodo * 0.02; // 2% Aporte Empresa
                   $totalAportesPatronales += $aporteFaovPatrono;
               }
               if ($emp['aplica_pie']) {
                   $montoPie = $sueldoBasePeriodo * 0.005; // 0.5% PIE Empleado
                   $dedEmpleado += $montoPie;
                   $detalles[] = ['cid' => 5, 'desc' => 'Retención Régimen Prestacional Empleo (0.5%)', 'tipo' => 'DEDUCCION', 'monto' => $montoPie];
               }
               $netoEmpleado = $asigEmpleado - $dedEmpleado;
               $stmtRecibo->execute([
                   'pid'  => $periodoId,
                   'eid'  => $emp['id'],
                   'sb'   => $sueldoBasePeriodo,
                   'asig' => $asigEmpleado,
                   'ded'  => $dedEmpleado,
                   'neto' => $netoEmpleado
               ]);
               $reciboId = (int)$db->lastInsertId();
               foreach ($detalles as $d) {
                   $stmtDet->execute([
                       'rid'   => $reciboId,
                       'cid'   => $d['cid'],
                       'desc'  => $d['desc'],
                       'tipo'  => $d['tipo'],
                       'monto' => $d['monto']
                   ]);
               }
               $totalAsignacionesPeriodo += $asigEmpleado;
               $totalDeduccionesPeriodo   += $dedEmpleado;
               $totalNetoPeriodo         += $netoEmpleado;
           }
           // Actualizar totales de la cabecera
           $db->prepare("
               UPDATE nomina_periodos 
               SET total_asignaciones = :asig, total_deducciones = :ded, 
                   total_aportes_patronales = :apo, total_neto_a_pagar = :neto
               WHERE id = :id
           ")->execute([
               'asig' => $totalAsignacionesPeriodo,
               'ded'  => $totalDeduccionesPeriodo,
               'apo'  => $totalAportesPatronales,
               'neto' => $totalNetoPeriodo,
               'id'   => $periodoId
           ]);
           Database::commit();
           return $periodoId;
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
   /**
    * 2. CIERRE, PAGO BANCARIO Y ASIENTO CONTABLE AUTOMÁTICO

    */
   public static function procesarPagoYCierre(int $periodoId, int $cuentaBancariaId, int $usuarioId): array {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           $stmt = $db->prepare("SELECT * FROM nomina_periodos WHERE id = :id FOR UPDATE");
           $stmt->execute(['id' => $periodoId]);
           $periodo = $stmt->fetch();
           if (!$periodo || $periodo['estado'] !== 'PRENOMINA') {
               throw new Exception("El período de nómina ya está cerrado o no existe.");
           }
           $montoNetoPagar = (float)$periodo['total_neto_a_pagar'];
           $totalAsig       = (float)$periodo['total_asignaciones'];
           $totalDed        = (float)$periodo['total_deducciones'];
           // 1. Desembolso en Tesorería / Bancos
           $refPago = "NOM-PAG-" . date('ymd') . "-{$periodoId}";
           $stmtBanco = $db->prepare("
                INSERT INTO movimientos_bancarios 
                (cuenta_id, tipo_movimiento, numero_referencia, monto, fecha_movimiento, conciliado, concepto, usuario_id)
                VALUES (:cta, 'RETIRO', :ref, :monto, :fec, 1, :conc, :usr)
            ");
            $stmtBanco->execute([
                'cta'   => $cuentaBancariaId,
                'ref'   => $refPago,
                'monto' => $montoNetoPagar,
                'fec'   => $periodo['fecha_pago'],
                'conc'  => "Pago de Nómina {$periodo['codigo_periodo']}",
                'usr'   => $usuarioId
            ]);
            $movimientoBancoId = (int)$db->lastInsertId();

            // Actualizar saldo de la cuenta bancaria
            $db->prepare("UPDATE cuentas_bancarias SET saldo_actual = saldo_actual - :monto WHERE id = :id")
               ->execute(['monto' => $montoNetoPagar, 'id' => $cuentaBancariaId]);

            // 2. Asiento Contable Automático (Partida Doble)
            $asientos = [
                [
                    'cuenta_id' => 23, // Gasto Sueldos y Salarios
                    'desc'      => "Gasto Laboral {$periodo['codigo_periodo']}",
                    'debe'      => $totalAsig,
                    'haber'     => 0.0
                ],
                [
                    'cuenta_id' => 20, // Retenciones IVSS / Parafiscales
                    'desc'      => "Retenciones de Ley Trabajadores {$periodo['codigo_periodo']}",
                    'debe'      => 0.0,
                    'haber'     => $totalDed
                ],
                [
                    'cuenta_id' => 5, // Cuenta Bancos Activo
                    'desc'      => "Desembolso Pago Nómina {$refPago}",
                    'debe'      => 0.0,
                    'haber'     => $montoNetoPagar
                ]
            ];

            // Registrar Comprobante de Diario
            $stmtComp = $db->prepare("
                INSERT INTO contabilidad_comprobantes 
                (numero_comprobante, fecha, tipo_origen, documento_origen_id, concepto_general, total_debe, total_haber, estado, usuario_id)
                VALUES (:num, :fec, 'MANUAL', :doc_id, :conc, :debe, :haber, 'ASENTADO', :usr)
            ");
            $stmtComp->execute([
                'num'    => "COMP-NOM-{$periodoId}",
                'fec'    => $periodo['fecha_pago'],
                'doc_id' => $periodoId,
                'conc'   => "Contabilización de Nómina y Retenciones {$periodo['codigo_periodo']}",
                'debe'   => $totalAsig,
                'haber'  => ($totalDed + $montoNetoPagar),
                'usr'    => $usuarioId
            ]);
           $comprobanteId = (int)$db->lastInsertId();
           $stmtAsientoDet = $db->prepare("
               INSERT INTO contabilidad_asientos_detalles (comprobante_id, cuenta_id, descripcion_renglon, debe, haber)
               VALUES (:comp, :cta, :desc, :debe, :haber)
           ");
           foreach ($asientos as $a) {
               $stmtAsientoDet->execute([
                   'comp'  => $comprobanteId,
                   'cta'   => $a['cuenta_id'],
                   'desc'  => $a['desc'],
                   'debe'  => $a['debe'],
                   'haber' => $a['haber']
               ]);
           }
           // 3. Cerrar Período
           $db->prepare("
               UPDATE nomina_periodos 
               SET estado = 'CERRADA_PAGADA', cuenta_bancaria_pago_id = :cta, 
                   movimiento_bancario_id = :mov, comprobante_contable_id = :comp
               WHERE id = :id
           ")->execute([
               'cta'  => $cuentaBancariaId,
               'mov'  => $movimientoBancoId,
               'comp' => $comprobanteId,
               'id'   => $periodoId
           ]);
           Database::commit();
           return [
               'status'         => 'success',
               'mensaje'        => 'Nómina pagada, transferida en bancos y contabilizada exitosamente.',
               'total_pagado'   => $montoNetoPagar,
               'comprobante_id' => $comprobanteId,
               'movimiento_id'  => $movimientoBancoId
           ];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
}