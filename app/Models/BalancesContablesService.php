<?php
namespace App\Models;
use App\Core\Database;
use PDO;
class BalancesContablesService {
   /**
    * Balance de Comprobación (Sumas y Saldos)
    */
   public static function getBalanceComprobacion(string $desde, string $hasta): array {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               pc.id,
               pc.codigo,
               pc.descripcion,
               pc.tipo_cuenta,
               pc.naturaleza,
               pc.nivel,
               COALESCE(SUM(ad.debe), 0) AS total_debe,
               COALESCE(SUM(ad.haber), 0) AS total_haber,
               CASE 
                   WHEN pc.naturaleza = 'DEUDORA' THEN (COALESCE(SUM(ad.debe), 0) - COALESCE(SUM(ad.haber), 0))
                   ELSE (COALESCE(SUM(ad.haber), 0) - COALESCE(SUM(ad.debe), 0))
               END AS saldo_final
           FROM contabilidad_plan_cuentas pc
           LEFT JOIN contabilidad_asientos_detalles ad ON pc.id = ad.cuenta_id
           LEFT JOIN contabilidad_comprobantes c ON ad.comprobante_id = c.id AND c.estado = 'ASENTADO' AND c.fecha BETW
           WHERE pc.estado = 1
           GROUP BY pc.id
           ORDER BY pc.codigo ASC
       ");
       $stmt->execute(['d1' => $desde, 'd2' => $hasta]);
       return $stmt->fetchAll();
   }
   /**
    * Estado de Resultados (Ingresos, Costos y Utilidad Operativa)
    */
   public static function getEstadoResultados(string $desde, string $hasta): array {

       $balance = self::getBalanceComprobacion($desde, $hasta);
       $ingresos = 0.0;
       $costos   = 0.0;
       $gastos   = 0.0;
       foreach ($balance as $row) {
           if ($row['tipo_cuenta'] === 'INGRESOS') $ingresos += (float)$row['saldo_final'];
           if ($row['tipo_cuenta'] === 'COSTOS')   $costos   += (float)$row['saldo_final'];
           if ($row['tipo_cuenta'] === 'GASTOS')   $gastos   += (float)$row['saldo_final'];
       }
       $utilidadBruta = $ingresos - $costos;
       $utilidadNeta  = $utilidadBruta - $gastos;
       return [
           'ingresos_operacionales' => $ingresos,
           'costos_ventas'          => $costos,
           'utilidad_bruta'         => $utilidadBruta,
           'gastos_operacionales'   => $gastos,
           'utilidad_neta_ejercicio'=> $utilidadNeta,
           'periodo'                => ['desde' => $desde, 'hasta' => $hasta]
       ];
   }
}