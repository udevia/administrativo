<?php
namespace App\Core;
use App\Core\Database;
use PDO;
use Exception;
class NotificationEngine {
   /**
    * Encola un comprobante digital para despacho automático por WhatsApp o Email

    */
    public static function encolarDocumento(string $tipoDoc, int $docId, string $destinatario, string $canal, string $nombreCliente, string $numDoc, ?string $pdfUrl = null): void {
        $db = Database::getConnection();
       $asunto = "Comprobante Digital {$tipoDoc} N° {$numDoc}";
        $mensaje = "Hola *{$nombreCliente}*, le hacemos llegar su comprobante digital de *{$tipoDoc} N° {$numDoc}* por usar nuestros servicios.";
       if ($pdfUrl) {
           $mensaje .= "\n\nPuede consultar su documento digital en el siguiente enlace: {$pdfUrl}";
       }
       $stmt = $db->prepare("
           INSERT INTO notificaciones_cola 
           (tipo_documento, documento_id, canal, destinatario, asunto, mensaje_cuerpo, archivo_adjunto_url, estado)
           VALUES (:tipo, :did, :canal, :dest, :asunto, :cuerpo, :adj, 'PENDIENTE')
       ");
       $stmt->execute([
           'tipo'   => $tipoDoc,
           'did'    => $docId,
           'canal'  => $canal,
           'dest'   => $destinatario,
           'asunto' => $asunto,
           'cuerpo' => $mensaje,
           'adj'    => $pdfUrl
       ]);
   }
   /**
    * Procesa la cola y envía mensajes vía Meta WhatsApp Cloud API
    */
   public static function despacharWhatsApp(array $itemNotificacion): bool {
       $db = Database::getConnection();
       $cfg = $db->query("SELECT * FROM notificaciones_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
       if (!$cfg || !$cfg['canal_whatsapp_activo'] || empty($cfg['whatsapp_phone_number_id'])) {
           return false;
       }
       $url = "https://graph.facebook.com/v19.0/{$cfg['whatsapp_phone_number_id']}/messages";
       // Formatear número internacional venezolano (+58)
       $telefono = preg_replace('/[^0-9]/', '', $itemNotificacion['destinatario']);
       if (str_starts_with($telefono, '0')) {
           $telefono = '58' . substr($telefono, 1);
       }
       $payload = [
           'messaging_product' => 'whatsapp',
           'recipient_type'    => 'individual',
           'to'                => $telefono,
           'type'              => 'text',
           'text'              => [
               'preview_url' => true,
               'body'        => $itemNotificacion['mensaje_cuerpo']
           ]
       ];
       $ch = curl_init($url);
       curl_setopt_array($ch, [
           CURLOPT_POST           => true,
           CURLOPT_POSTFIELDS     => json_encode($payload),
           CURLOPT_HTTPHEADER     => [
               'Authorization: Bearer ' . $cfg['whatsapp_access_token'],
               'Content-Type: application/json'
           ],
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_TIMEOUT        => 15
       ]);
       $response = curl_exec($ch);
       $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       curl_close($ch);

       $exito = ($httpCode === 200 || $httpCode === 201);
       $db->prepare("
           UPDATE notificaciones_cola 
           SET estado = :est, respuesta_proveedor = :res, enviado_at = NOW(), intentos = intentos + 1
           WHERE id = :id
       ")->execute([
           'est' => $exito ? 'ENVIADO' : 'FALLIDO',
           'res' => $response,
           'id'  => $itemNotificacion['id']
       ]);
       return $exito;
   }
}