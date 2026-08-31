<?php
namespace App\Core\Pdf;
class DocumentPdfService {
   /**
    * Renderiza el HTML del Ticket Térmico de 80mm
    */
   public static function generarTicketHtml(array $venta, array $detalles, array $cliente, array $empresa): string {
       ob_start();
       ?>
       <!DOCTYPE html>
       <html>
       <head>
           <meta charset="utf-8">
           <style>
               body { font-family: monospace; font-size: 11px; margin: 0; padding: 5px; width: 280px; }
               .center { text-align: center; }
               .right { text-align: right; }
               .bold { font-weight: bold; }
               .line { border-bottom: 1px dashed #000; margin: 5px 0; }
               table { width: 100%; border-collapse: collapse; font-size: 11px; }
               td { vertical-align: top; }
               .qr-container { text-align: center; margin-top: 8px; }
           </style>
       </head>
       <body>
           <div class="center">
               <span class="bold" style="font-size: 13px;"><?= htmlspecialchars($empresa['razon_social']) ?></span><br>
               RIF: <?= htmlspecialchars($empresa['rif']) ?><br>
               <?= htmlspecialchars($empresa['direccion_fiscal']) ?><br>
               Tel: <?= htmlspecialchars($empresa['telefono'] ?? '') ?>
           </div>
           
           <div class="line"></div>
           
           <div>
               <span class="bold"><?= htmlspecialchars($venta['tipo_documento']) ?>:</span> <?= htmlspecialchars($venta['numero_documento']) ?><br>
               <?php if (!empty($venta['control_fiscal'])): ?>
                   <span>N° Control: <?= htmlspecialchars($venta['control_fiscal']) ?></span><br>
               <?php endif; ?>
               Fecha: <?= date('d/m/Y H:i', strtotime($venta['created_at'])) ?><br>
               Cliente: <?= htmlspecialchars($cliente['razon_social']) ?><br>
               RIF/CI: <?= htmlspecialchars($cliente['documento_fiscal']) ?><br>
               Condición: <?= htmlspecialchars($venta['condicion_pago']) ?>
           </div>
           
           <div class="line"></div>
           
           <table>
               <thead>
                   <tr class="bold">
                       <td>Cant. Descrip.</td>
                       <td class="right">Total</td>
                   </tr>
               </thead>
               <tbody>
                   <?php foreach ($detalles as $d): ?>
                   <tr>
                       <td>
                           <?= (float)$d['cantidad'] ?> x <?= number_format($d['precio_unitario'], 2) ?><br>
                           <?= htmlspecialchars(substr($d['descripcion'], 0, 24)) ?>
                       </td>
                       <td class="right"><?= number_format($d['total'], 2) ?></td>
                   </tr>
                   <?php endforeach; ?>
               </tbody>
           </table>
           
           <div class="line"></div>

           <table>
               <tr><td>Subtotal:</td><td class="right"><?= number_format($venta['subtotal_neto'], 2) ?></td></tr>
               <?php if ($venta['monto_exento'] > 0): ?>
                   <tr><td>Exento:</td><td class="right"><?= number_format($venta['monto_exento'], 2) ?></td></tr>
               <?php endif; ?>
               <tr><td>Base Imponible:</td><td class="right"><?= number_format($venta['base_imponible'], 2) ?></td></tr>
               <tr><td>IVA (16%):</td><td class="right"><?= number_format($venta['monto_iva'], 2) ?></td></tr>
               <tr class="bold" style="font-size: 13px;">
                   <td>TOTAL $:</td>
                   <td class="right"><?= number_format($venta['total_general'], 2) ?></td>
               </tr>
               <tr class="bold">
                   <td>TOTAL Bs:</td>
                   <td class="right"><?= number_format($venta['total_general'] * $venta['tasa_cambio'], 2) ?></td>
               </tr>
           </table>
           <?php if (!empty($venta['hka_qr_url'])): ?>
           <div class="qr-container">
               <img src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=<?= urlencode($venta['hka_qr_url']) ?>"><br>
               <span style="font-size: 8px;">CUFE: <?= htmlspecialchars(substr($venta['hka_cufe'], 0, 20)) ?>...</span>
           </div>
           <?php endif; ?>
           <div class="center" style="margin-top: 10px;">
               ¡Gracias por su compra!<br>
               Sistema Administrativo Web
           </div>
       </body>
       </html>
       <?php
       return ob_get_clean();
   }
}