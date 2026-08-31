<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use PDO;
use Exception;

class WhatsAppService {
    private const GRAPH_API_BASE = 'https://graph.facebook.com/v18.0/';

    /**
     * Envía un mensaje con documento PDF adjunto vía Meta Cloud API
     */
    public static function enviarDocumentoPdf(
        string $telefonoDestino,
        string $tipoDoc,
        string $numeroDoc,
        string $pdfUrl,
        string $nombreCliente
    ): array {
        $db = Database::getConnection();
        $cfg = $db->query("SELECT * FROM notificaciones_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

        if (!$cfg || empty($cfg['whatsapp_access_token']) || empty($cfg['whatsapp_phone_number_id'])) {
            return [
                'status' => 'disabled',
                'message' => 'Configuración de WhatsApp Meta Cloud API incompleta.'
            ];
        }

        $url = self::GRAPH_API_BASE . $cfg['whatsapp_phone_number_id'] . '/messages';
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $telefonoDestino,
            'type' => 'document',
            'document' => [
                'link' => $pdfUrl,
                'caption' => "Estimado(a) {$nombreCliente}, adjunto encontrará su {$tipoDoc} N° {$numeroDoc}.",
                'filename' => "{$tipoDoc}_{$numeroDoc}.pdf"
            ]
        ];

        return self::ejecutarCurl($url, $payload, $cfg['whatsapp_access_token'], $tipoDoc, $numeroDoc, $telefonoDestino);
    }

    /**
     * Envía un mensaje de texto simple vía Meta Cloud API
     */
    public static function enviarTexto(string $telefonoDestino, string $mensaje): array {
        $db = Database::getConnection();
        $cfg = $db->query("SELECT * FROM notificaciones_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

        if (!$cfg || empty($cfg['whatsapp_access_token']) || empty($cfg['whatsapp_phone_number_id'])) {
            return [
                'status' => 'disabled',
                'message' => 'WhatsApp Meta Cloud API no configurado.'
            ];
        }

        $url = self::GRAPH_API_BASE . $cfg['whatsapp_phone_number_id'] . '/messages';
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $telefonoDestino,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $mensaje
            ]
        ];

        return self::ejecutarCurl($url, $payload, $cfg['whatsapp_access_token'], 'TEXTO', '', $telefonoDestino);
    }

    private static function ejecutarCurl(string $url, array $payload, string $token, string $tipoDoc, string $numeroDoc, string $destinatario): array {
        $db = Database::getConnection();
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $exito = ($httpCode >= 200 && $httpCode < 300);

        // Registro en cola de notificaciones
        try {
            $stmt = $db->prepare("
                INSERT INTO notificaciones_cola (
                    tipo_documento, documento_id, canal, destinatario, asunto,
                    mensaje_cuerpo, archivo_adjunto_url, estado, respuesta_proveedor, enviado_at
                ) VALUES (
                    :tipo, 0, 'WHATSAPP', :dest, :asunto,
                    :cuerpo, :adjunto, :estado, :resp, :fec
                )
            ");
            $stmt->execute([
                'tipo' => $tipoDoc,
                'dest' => $destinatario,
                'asunto' => "Envío {$tipoDoc} {$numeroDoc}",
                'cuerpo' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'adjunto' => $payload['document']['link'] ?? null,
                'estado' => $exito ? 'ENVIADO' : 'FALLIDO',
                'resp' => $response ?: $error,
                'fec' => $exito ? date('Y-m-d H:i:s') : null
            ]);
        } catch (\Throwable $e) {
            error_log("Error registrando log de WhatsApp: " . $e->getMessage());
        }

        return [
            'status' => $exito ? 'success' : 'error',
            'http_code' => $httpCode,
            'response' => json_decode((string)$response, true) ?? $response
        ];
    }
}
