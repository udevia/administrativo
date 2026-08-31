<?php
namespace App\Core\Pdf;
use App\Core\Database;
use App\Core\PdfEngine;
use App\Core\TemplateEngine;
use Exception;
class ReciboNominaPdf {
   public static function generar(int $reciboId): void {
       $db = Database::getConnection();
       // 1. Obtener datos del recibo y empleado
       $stmt = $db->prepare("
           SELECT 
               r.*,
               np.codigo_periodo, np.fecha_inicio, np.fecha_fin, np.fecha_pago,
               e.codigo AS empleado_codigo, e.cedula, e.nombres, e.apellidos, e.cargo, e.fecha_ingreso,

               d.descripcion AS departamento
           FROM nomina_recibos r
           INNER JOIN nomina_periodos np ON r.periodo_id = np.id
           INNER JOIN nomina_empleados e ON r.empleado_id = e.id
           INNER JOIN departamentos d ON e.departamento_id = d.id
           WHERE r.id = :id
       ");
       $stmt->execute(['id' => $reciboId]);
       $recibo = $stmt->fetch();
       if (!$recibo) throw new Exception("Recibo de nómina no encontrado.");
       // 2. Obtener desglose de asignaciones y deducciones
       $stmtDet = $db->prepare("SELECT * FROM nomina_recibos_detalles WHERE recibo_id = :id ORDER BY tipo ASC");
       $stmtDet->execute(['id' => $reciboId]);
       $detalles = $stmtDet->fetchAll();
       $html = "
       <!DOCTYPE html>
       <html>
       <head>
           <meta charset='utf-8'>
           <style>
               body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; margin: 20px; color: #1e293b; }
               .header { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
               .bold { font-weight: bold; }
               table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10px; }
               th { background-color: #f1f5f9; padding: 5px; text-align: left; border-bottom: 1px solid #cbd5e1; }
               td { padding: 5px; border-bottom: 1px solid #f1f5f9; }
               .right { text-align: right; }
               .firmas { margin-top: 50px; width: 100%; }
                .firma-box { border-top: 1px solid #000; width: 40%; text-align: center; font-size: 10px; padding-top: 4px; }
           </style>
       </head>
       <body>
           <div class='header'>
               <span class='bold' style='font-size: 14px;'>{{empresa_nombre}}</span><br>
               <span>RIF: {{empresa_rif}}</span><br>
               <span class='bold'>RECIBO DE PAGO DE NÓMINA - PERÍODO: {$recibo['codigo_periodo']}</span><br>
                <span>Lapso: " . date('d/m/Y', strtotime($recibo['fecha_inicio'])) . " al " . date('d/m/Y', strtotime($recibo['fecha_fin'])) . "</span>
           </div>
           <table style='margin-bottom: 12px;'>
               <tr>
                   <td><b>Trabajador:</b> {$recibo['nombres']} {$recibo['apellidos']}</td>
                   <td><b>C.I.:</b> {$recibo['cedula']}</td>
                   <td><b>Código:</b> {$recibo['empleado_codigo']}</td>
               </tr>
               <tr>
                   <td><b>Cargo:</b> {$recibo['cargo']}</td>
                   <td><b>Departamento:</b> {$recibo['departamento']}</td>
                   <td><b>Ingreso:</b> " . date('d/m/Y', strtotime($recibo['fecha_ingreso'])) . "</td>
               </tr>
           </table>
           <table>
               <thead>
                   <tr>
                       <th>Concepto</th>
                       <th class='right'>Asignaciones ($)</th>
                       <th class='right'>Deducciones ($)</th>
                   </tr>
               </thead>
               <tbody>";
       foreach ($detalles as $d) {
           $asig = $d['tipo'] === 'ASIGNACION' ? number_format($d['monto'], 2) : '-';
           $ded  = $d['tipo'] === 'DEDUCCION' ? number_format($d['monto'], 2) : '-';
           $html .= "
               <tr>
                   <td>{$d['descripcion']}</td>
                   <td class='right font-mono'>{$asig}</td>
                   <td class='right font-mono text-red-600'>{$ded}</td>
               </tr>";

       }
       $html .= "
               </tbody>
               <tfoot>
                   <tr class='bold' style='background-color: #f8fafc;'>
                       <td>TOTALES:</td>
                       <td class='right font-mono'>" . number_format($recibo['total_asignaciones'], 2) . "</td>
                        <td class='right font-mono text-red-600'>" . number_format($recibo['total_deducciones'], 2) . "</td>
                   </tr>
                   <tr class='bold' style='font-size: 12px; background-color: #e2e8f0;'>
                       <td colspan='2'>NETO LÍQUIDO A COBRAR:</td>
                       <td class='right font-mono'>$ " . number_format($recibo['neto_cobrar'], 2) . "</td>
                   </tr>
               </tfoot>
           </table>
           <table class='firmas'>
               <tr>
                   <td style='border:0;'><div class='firma-box'>Elaborado por (RRHH)</div></td>
                    <td style='border:0;'><div class='firma-box' style='margin-left: auto;'>Conforme Trabajador (Firma / Huella)</div></td>
               </tr>
           </table>
       </body>
       </html>";
       $htmlFinal = TemplateEngine::renderFormat($html, []);
       PdfEngine::streamPdf($htmlFinal, "Recibo_{$recibo['cedula']}_{$recibo['codigo_periodo']}.pdf");
   }
}