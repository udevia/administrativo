<?php
namespace App\Core;
use App\Core\Database;
use PDO;
use Exception;
class TelegramEngine {
   private const API_BASE = 'https://api.telegram.org/bot';
   /**
    * Envía un mensaje de texto formateado en Markdown / HTML
    */
   public static function enviarTexto(string $chatId, string $texto, ?array $inlineKeyboard = null): array {
       $cfg = self::getConfig();
       if (!$cfg || !$cfg['estado'] || empty($cfg['bot_token'])) {
           return ['status' => 'disabled', 'mensaje' => 'Telegram Bot inactivo o no configurado.'];
       }
       $url = self::API_BASE . $cfg['bot_token'] . '/sendMessage';
       $payload = [
           'chat_id'    => $chatId,
           'text'       => $texto,
           'parse_mode' => 'HTML'
       ];
       if ($inlineKeyboard) {
           $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard]);
       }
       return self::ejecutarCurl($url, $payload, $chatId, $texto);
   }
   /**
    * Envía un archivo PDF generado por el sistema directamente al chat
    */
    public static function enviarDocumentoPdf(string $chatId, string $pdfUrlPublica, string $caption, string $nombreArchivo = 'documento.pdf'): array {
       $cfg = self::getConfig();
       if (!$cfg || !$cfg['estado'] || empty($cfg['bot_token'])) {
           return ['status' => 'disabled', 'mensaje' => 'Telegram Bot inactivo.'];
       }
       $url = self::API_BASE . $cfg['bot_token'] . '/sendDocument';
       $payload = [
           'chat_id'    => $chatId,
           'document'   => $pdfUrlPublica,
           'caption'    => $caption,
           'parse_mode' => 'HTML'
       ];
       return self::ejecutarCurl($url, $payload, $chatId, $caption, $pdfUrlPublica);
   }
   /**
    * Dispara una alerta al grupo gerencial de la empresa
    */
   public static function notificarGerencia(string $mensajeHtml, ?array $inlineKeyboard = null): void {
       $cfg = self::getConfig();
       if ($cfg && !empty($cfg['canal_gerencial_chat_id'])) {
           self::enviarTexto($cfg['canal_gerencial_chat_id'], $mensajeHtml, $inlineKeyboard);
       }
   }
   private static function getConfig(): ?array {
       $db = Database::getConnection();
       return $db->query("SELECT * FROM telegram_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC) ?: null;
   }
    private static function ejecutarCurl(string $url, array $payload, string $chatId, string $texto, ?string $pdfUrl = null): array {

       $db = Database::getConnection();
       $ch = curl_init($url);
       curl_setopt_array($ch, [
           CURLOPT_POST           => true,
           CURLOPT_POSTFIELDS     => $payload,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_TIMEOUT        => 15,
           CURLOPT_SSL_VERIFYPEER => true
       ]);
       $response = curl_exec($ch);
       $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       curl_close($ch);
       $resJson = json_decode($response, true);
       $exito = ($httpCode === 200 && isset($resJson['ok']) && $resJson['ok'] === true);
       // Registrar log
       $stmt = $db->prepare("
            INSERT INTO telegram_mensajes_log (chat_id, tipo_mensaje, mensaje_texto, documento_pdf_url, estado, telegram_message_id, json_error)
           VALUES (:chat, 'NOTIFICACION_CLIENTE', :txt, :pdf, :est, :mid, :err)
       ");
       $stmt->execute([
           'chat' => $chatId,
           'txt'  => substr($texto, 0, 500),
           'pdf'  => $pdfUrl,
           'est'  => $exito ? 'ENVIADO' : 'ERROR',
           'mid'  => $resJson['result']['message_id'] ?? null,
           'err'  => $exito ? null : $response
       ]);
       return [
           'status'     => $exito ? 'success' : 'error',
           'message_id' => $resJson['result']['message_id'] ?? null,
           'response'   => $resJson
       ];
   }
}