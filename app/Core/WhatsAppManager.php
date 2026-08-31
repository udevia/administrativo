<?php
namespace App\Core;
use App\Core\Database;
use PDO;
class WhatsAppManager {
   /**
    * Resuelve el método de envío según la preferencia del cliente
    */
   public static function procesarEnvio(
       string $telefonoDestino,
       string $tipoDoc,
       string $numeroDoc,
       string $pdfUrlPublica,
       string $nombreCliente,
       float $montoTotal = 0.0
   ): array {
       $db = Database::getConnection();
       $cfg = $db->query("SELECT * FROM notificaciones_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
       $modo = $cfg['modo_whatsapp'] ?? 'DESKTOP_WEB_GRATIS';
       // Formatear número internacional (+58 para Venezuela)
       $telefono = preg_replace('/[^0-9]/', '', $telefonoDestino);
       if (str_starts_with($telefono, '0')) {
           $telefono = '58' . substr($telefono, 1);
       }
       // Construir texto del mensaje
        $textoMensaje = "Hola *{$nombreCliente}*, le hacemos llegar su comprobante digital de *{$tipoDoc} N° {$numeroDoc}*";
       if ($montoTotal > 0) {
           $textoMensaje .= " por un monto total de *$" . number_format($montoTotal, 2) . "*";
       }
        $textoMensaje .= ".\n\nPuede consultar y descargar su documento PDF aquí:\n{$pdfUrlPublica}\n\n¡Gracias por su preferencia!";
       // 1. MODO DESKTOP / WEB (Gratuito)
       if ($modo === 'DESKTOP_WEB_GRATIS') {
           $urlWa = "https://wa.me/{$telefono}?text=" . urlencode($textoMensaje);
           return [
               'modo'      => 'DESKTOP_WEB',
               'url_wa'    => $urlWa,
               'mensaje'   => 'Enlace generado para WhatsApp Desktop / Web.',
               'auto_open' => true
           ];

       }
       // 2. MODO META CLOUD API (Automático en segundo plano)
       if ($modo === 'META_CLOUD_API') {
           $resApi = WhatsAppService::enviarDocumentoPdf(
               $telefono,
               $tipoDoc,
               $numeroDoc,
               $pdfUrlPublica,
               $nombreCliente
           );
           return array_merge(['modo' => 'META_API'], $resApi);
       }
       return ['modo' => 'DESACTIVADO', 'mensaje' => 'Notificaciones por WhatsApp desactivadas.'];
   }
}