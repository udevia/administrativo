<?php
namespace App\Models;
use App\Core\Database;
use PDO;
use Exception;
class ActivosFijosService {
   /**
    * Registra un nuevo activo fijo y calcula su valor en libros inicial
    */
   public static function crearActivo(array $datos): int {
       $db = Database::getConnection();
       $costo      = (float)$datos['costo_adquisicion_usd'];
       $residual   = (float)($datos['valor_residual_usd'] ?? 0.0);
       $mesesVida  = (int)$datos['vida_util_meses'];
       if ($costo <= 0 || $mesesVida <= 0) {
           throw new Exception("El costo de adquisición y la vida útil deben ser superiores a cero.");
       }
       $stmt = $db->prepare("
           INSERT INTO activos_fijos (
               codigo_placa_activo, categoria_id, departamento_id, responsable_usuario_id,
               descripcion, marca_modelo, numero_serial, fecha_adquisicion, fecha_inicio_depreciacion,
               numero_factura_compra, proveedor_id, costo_adquisicion_usd, tasa_cambio_adquisicion,
               valor_residual_usd, vida_util_meses, meses_depreciados, depreciacion_acumulada_usd,
               valor_en_libros_usd, metodo_depreciacion, estado
           ) VALUES (
               :cod, :cat, :dep, :resp,
               :desc, :mm, :serial, :f_adq, :f_dep,
               :fact, :prov, :costo, :tasa,
               :res, :vida, 0, 0.0000,
               :libros, :metodo, 'ACTIVO'
           )
       ");
       $stmt->execute([
           'cod'    => trim($datos['codigo_placa_activo']),
           'cat'    => (int)$datos['categoria_id'],
           'dep'    => (int)$datos['departamento_id'],
           'resp'   => !empty($datos['responsable_usuario_id']) ? (int)$datos['responsable_usuario_id'] : null,
           'desc'   => trim($datos['descripcion']),
           'mm'     => trim($datos['marca_modelo'] ?? ''),
           'serial' => trim($datos['numero_serial'] ?? ''),
           'f_adq'  => $datos['fecha_adquisicion'],
           'f_dep'  => $datos['fecha_inicio_depreciacion'],
           'fact'   => trim($datos['numero_factura_compra'] ?? ''),
           'prov'   => !empty($datos['proveedor_id']) ? (int)$datos['proveedor_id'] : null,
           'costo'  => $costo,
           'tasa'   => (float)($datos['tasa_cambio_adquisicion'] ?? 1.0),
           'res'    => $residual,
           'vida'   => $mesesVida,
           'libros' => $costo,
           'metodo' => $datos['metodo_depreciacion'] ?? 'LINEA_RECTA'
       ]);
       return (int)$db->lastInsertId();
   }
   /**
    * PROCESAMIENTO MENSUAL DE DEPRECIACIÓN Y ASIENTO CONTABLE AUTOMÁTICO
    */
   public static function ejecutarCierreDepreciacionMensual(int $ano, int $mes, int $usuarioId = 1): array {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           $fechaCierre = sprintf('%04d-%02d-01', $ano, $mes);
           // Obtener activos depreciables activos que no hayan completado su vida útil
           $stmtAct = $db->query("
               SELECT af.*, ac.cuenta_depreciacion_acumulada_id, ac.cuenta_gasto_depreciacion_id, ac.nombre AS categori
               FROM activos_fijos af
               INNER JOIN activos_categorias ac ON af.categoria_id = ac.id
               WHERE af.estado = 'ACTIVO' 
                 AND af.metodo_depreciacion = 'LINEA_RECTA'
                 AND af.fecha_inicio_depreciacion <= '{$fechaCierre}'
                 AND af.meses_depreciados < af.vida_util_meses
           ");
           $activos = $stmtAct->fetchAll();
           if (empty($activos)) {
               Database::rollBack();
                return ['status' => 'info', 'mensaje' => "No hay activos pendientes por depreciar para el período {$mes}/{$ano}."];
           }
           $asientosContables = [];
           $totalDepreciacionMes = 0.0;
           $activosProcesados = 0;
           $stmtInsHist = $db->prepare("
               INSERT INTO activos_depreciaciones_mensuales 
               (activo_id, ano, mes, monto_depreciacion_mes_usd, depreciacion_acumulada_momento_usd, valor_libros_momento_usd)
               VALUES (:aid, :ano, :mes, :monto, :acum, :libros)
               ON DUPLICATE KEY UPDATE monto_depreciacion_mes_usd = VALUES(monto_depreciacion_mes_usd)
           ");
           $stmtUpdAct = $db->prepare("
               UPDATE activos_fijos SET
                   meses_depreciados = meses_depreciados + 1,
                   depreciacion_acumulada_usd = :acum,
                   valor_en_libros_usd = :libros,
                   estado = CASE WHEN meses_depreciados + 1 >= vida_util_meses THEN 'TOTALMENTE_DEPRECIADO' ELSE 'ACTIVO' END
               WHERE id = :id
           ");
           // Agrupador para balance contable por cuenta
           $cuentasGasto = [];
           $cuentasAcumulada = [];
           foreach ($activos as $a) {
               $costo       = (float)$a['costo_adquisicion_usd'];
               $residual    = (float)$a['valor_residual_usd'];
               $vidaMeses   = (int)$a['vida_util_meses'];
               $depAcumPrev = (float)$a['depreciacion_acumulada_usd'];
               // Cuota mensual fija
               $montoDepreciable = $costo - $residual;
               $cuotaMes = round($montoDepreciable / $vidaMeses, 4);
               // Ajuste si es el último mes para no sobrepasar el valor residual
               if (($depAcumPrev + $cuotaMes) > $montoDepreciable) {
                   $cuotaMes = $montoDepreciable - $depAcumPrev;
               }
               $nuevaAcumulada = $depAcumPrev + $cuotaMes;
               $nuevoValorLibros = $costo - $nuevaAcumulada;
               // 1. Histórico de Depreciación
               $stmtInsHist->execute([
                   'aid'    => $a['id'],
                   'ano'    => $ano,
                   'mes'    => $mes,
                   'monto'  => $cuotaMes,
                   'acum'   => $nuevaAcumulada,
                   'libros' => $nuevoValorLibros
               ]);
               // 2. Actualizar Activo Maestro
               $stmtUpdAct->execute([
                   'acum'   => $nuevaAcumulada,
                   'libros' => $nuevoValorLibros,
                   'id'     => $a['id']
               ]);
               // 3. Acumular importes para el comprobante
               $ctaGasto = (int)$a['cuenta_gasto_depreciacion_id'];
               $ctaAcum  = (int)$a['cuenta_depreciacion_acumulada_id'];
               $cuentasGasto[$ctaGasto] = ($cuentasGasto[$ctaGasto] ?? 0.0) + $cuotaMes;
               $cuentasAcumulada[$ctaAcum] = ($cuentasAcumulada[$ctaAcum] ?? 0.0) + $cuotaMes;
               $totalDepreciacionMes += $cuotaMes;
               $activosProcesados++;
           }
           // 4. Generar Asiento Contable Automático (Partida Doble)
           $comprobanteId = null;
           if ($totalDepreciacionMes > 0) {
               $asientos = [];
               // DEBE: Cuentas de Gasto Depreciación
               foreach ($cuentasGasto as $ctaId => $monto) {
                   $asientos[] = [
                       'cuenta_id' => $ctaId,
                       'desc'      => "Gasto Depreciación Activos Fijos {$mes}/{$ano}",
                       'debe'      => $monto,
                       'haber'     => 0.0
                   ];
               }
               // HABER: Cuentas de Depreciación Acumulada
               foreach ($cuentasAcumulada as $ctaId => $monto) {
                   $asientos[] = [
                       'cuenta_id' => $ctaId,
                       'desc'      => "Depreciación Acumulada Período {$mes}/{$ano}",

                       'debe'      => 0.0,
                       'haber'     => $monto
                   ];
               }
               $numComp = sprintf('COMP-DEP-%04d%02d', $ano, $mes);
               $stmtComp = $db->prepare("
                   INSERT INTO contabilidad_comprobantes 
                   (numero_comprobante, fecha, tipo_origen, concepto_general, total_debe, total_haber, estado, usuario_id)
                   VALUES (:num, :fec, 'MANUAL', :conc, :debe, :haber, 'ASENTADO', :usr)
                   ON DUPLICATE KEY UPDATE total_debe = VALUES(total_debe), total_haber = VALUES(total_haber)
               ");
               $stmtComp->execute([
                   'num'   => $numComp,
                   'fec'   => date('Y-m-t', strtotime("{$ano}-{$mes}-01")), // Último día del mes
                   'conc'  => "Depreciación Mensual de Activos Fijos Período {$mes}/{$ano}",
                   'debe'  => $totalDepreciacionMes,
                   'haber' => $totalDepreciacionMes,
                   'usr'   => $usuarioId
               ]);
               $comprobanteId = (int)$db->lastInsertId();
               $stmtDet = $db->prepare("
                    INSERT INTO contabilidad_asientos_detalles (comprobante_id, cuenta_id, descripcion_renglon, debe, haber)
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
                // Vincular comprobante a los registros del mes
                $db->prepare("UPDATE activos_depreciaciones_mensuales SET comprobante_contable_id = :cid WHERE ano = :ano AND mes = :mes")
                   ->execute(['cid' => $comprobanteId, 'ano' => $ano, 'mes' => $mes]);
            }
            Database::commit();
            return [
                'status'             => 'success',
                'activos_procesados' => $activosProcesados,
                'total_depreciado'   => round($totalDepreciacionMes, 2),
                'comprobante_id'     => $comprobanteId,
                'mensaje'            => "Cierre de depreciación completado para {$activosProcesados} activos."
            ];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
}