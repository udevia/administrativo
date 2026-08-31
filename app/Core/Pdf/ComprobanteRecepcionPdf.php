<?php
namespace App\Core\Pdf;
use App\Core\Database;
use App\Core\PdfEngine;
use App\Core\TemplateEngine;
use Exception;
class ComprobanteRecepcionPdf {
   public static function generar(int $ordenId): void {
       $db = Database::getConnection();
       $stmt = $db->prepare("
           SELECT 
               ot.*, ts.nombre AS tipo_servicio,
               c.razon_social AS cliente_nombre, c.documento_fiscal AS cliente_rif,
               c.telefono AS cliente_telefono, c.direccion_fiscal AS cliente_direccion,
               u.nombre AS tecnico_nombre
           FROM sat_ordenes_trabajo ot
           INNER JOIN sat_tipos_servicio ts ON ot.tipo_servicio_id = ts.id
           INNER JOIN clientes c ON ot.cliente_id = c.id
           LEFT JOIN usuarios u ON ot.tecnico_responsable_id = u.id
           WHERE ot.id = :id
       ");
       $stmt->execute(['id' => $ordenId]);
       $ot = $stmt->fetch();
       if (!$ot) throw new Exception("Orden de recepción no encontrada.");
       // Obtener Metadatos / Campos Dinámicos
       $stmtCampos = $db->prepare("
           SELECT cp.nombre_campo, ovc.valor
           FROM sat_ordenes_valores_campos ovc
           INNER JOIN sat_campos_personalizados cp ON ovc.campo_id = cp.id
           WHERE ovc.orden_id = :id
           ORDER BY cp.orden ASC
       ");
       $stmtCampos->execute(['id' => $ordenId]);
       $campos = $stmtCampos->fetchAll();
       $tablaCampos = '';
       foreach ($campos as $c) {
            $tablaCampos .= "<tr><td style='width:40%;font-weight:bold;background:#f8fafc;'>{$c['nombre_campo']}:</td><td>{$c['valor']}</td></tr>";
       }
       $html = "
       <!DOCTYPE html>
       <html>
       <head>
           <meta charset='utf-8'>
           <style>
               body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; margin: 20px; color: #1e293b; }
               .header { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
               .bold { font-weight: bold; }
               table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10px; }
               th, td { padding: 5px; border: 1px solid #cbd5e1; }
               .clausulas { font-size: 8px; color: #64748b; margin-top: 15px; text-align: justify; }
               .firmas { margin-top: 40px; width: 100%; border: 0; }
                .firma-box { border-top: 1px solid #000; width: 40%; text-align: center; font-size: 9px; padding-top: 4px; }
           </style>
       </head>
       <body>
           <div class='header'>
               <span class='bold' style='font-size: 14px;'>{{empresa_nombre}}</span><br>
               <span>RIF: {{empresa_rif}}</span><br>
                <span class='bold' style='font-size: 12px;'>COMPROBANTE DE RECEPCIÓN Y SERVICIO TÉCNICO: {$ot['numero_orden']}</span><br>
                <span>Fecha: " . date('d/m/Y H:i', strtotime($ot['fecha_recepcion'])) . " | Servicio: {$ot['tipo_servicio']}</span>
           </div>
           <table>

               <tr>
                   <td style='width:50%;'><b>Cliente:</b> {$ot['cliente_nombre']} ({$ot['cliente_rif']})</td>
                   <td style='width:50%;'><b>Teléfono:</b> {$ot['cliente_telefono']}</td>
               </tr>
               <tr>
                   <td><b>Técnico Asignado:</b> " . ($ot['tecnico_nombre'] ?: 'Por asignar') . "</td>
                    <td><b>Promesa de Entrega:</b> " . ($ot['fecha_promesa_entrega'] ? date('d/m/Y H:i', strtotime($ot['fecha_promesa_entrega'])) : 'N/A') . "</td>
               </tr>
           </table>
           <h4 style='margin: 10px 0 2px 0; font-size: 11px;'>DATOS Y CARACTERÍSTICAS DEL BIEN RECIBIDO</h4>
           <table>{$tablaCampos}</table>
           <h4 style='margin: 10px 0 2px 0; font-size: 11px;'>FALLA O REQUERIMIENTO REPORTADO</h4>
           <div style='padding: 6px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 10px;'>
               {$ot['falla_reportada_cliente']}
           </div>
           <div style='margin-top: 6px;'>
               <b>Accesorios / Adicionales Recibidos:</b> " . ($ot['accesorios_recibidos'] ?: 'Ninguno reportado') . "
           </div>
           <div class='clausulas'>
                <b>CONDICIONES DE SERVICIO:</b> Todo equipo o bien que permanezca más de 30 días continuos en taller después de su reparación, causará cargos por almacenaje. La empresa no se hace responsable por equipos no reclamados después de 90 días.
           </div>
           <table class='firmas'>
               <tr>
                   <td style='border:0;'><div class='firma-box'>Receptor Autorizado</div></td>
                    <td style='border:0;'><div class='firma-box' style='margin-left: auto;'>Conforme Cliente (Firma)</div></td>
               </tr>
           </table>
       </body>
       </html>";
       $htmlFinal = TemplateEngine::renderFormat($html, []);
       PdfEngine::streamPdf($htmlFinal, "Recepcion_{$ot['numero_orden']}.pdf");
   }
}