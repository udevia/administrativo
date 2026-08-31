<?php
namespace App\Controllers;
use App\Core\Database;
use App\Core\TelegramEngine;
use PDO;
class TelegramWebhookController {
    public function procesarWebhook(): void {
        $this->handle();
    }

    public function handle(): void {
       $content = file_get_contents('php://input');
       $update = json_decode($content, true);
       if (!$update) {
           http_response_code(200);
           exit;
       }
       // 1. Manejo de Comandos de Texto
       if (isset($update['message']['text'])) {
           $chatId  = (string)$update['message']['chat']['id'];
           $texto   = trim($update['message']['text']);
           $usuario = $update['message']['from']['first_name'] ?? 'Usuario';
           $this->procesarComando($chatId, $texto, $usuario);
       }
       // 2. Manejo de Botones Interactivos (Callbacks)
       if (isset($update['callback_query'])) {
           $callback = $update['callback_query'];

           $chatId   = (string)$callback['message']['chat']['id'];
           $data     = $callback['data']; // Ej: "aprobar_credito:12"
           $this->procesarCallback($chatId, $data, $callback['id']);
       }
       http_response_code(200);
       echo json_encode(['ok' => true]);
   }
   private function procesarComando(string $chatId, string $texto, string $nombreUsuario): void {
       $db = Database::getConnection();
       switch (strtolower(explode(' ', $texto)[0])) {
           case '/start':
                $msg = "👋 ¡Hola, <b>{$nombreUsuario}</b>!\n\nBienvenido al Bot de Control del <b>Sistema Administrativo</b>\n"
                     . "📊 <b>/ventas_hoy</b> - Resumen de facturación del día\n"
                    . "⚠️ <b>/stock_critico</b> - Productos en punto de reorden\n"
                    . "💰 <b>/bancos</b> - Saldos disponibles en tesorería\n"
                    . "🆔 <b>/mi_id</b> - Muestra tu Chat ID de Telegram";
               TelegramEngine::enviarTexto($chatId, $msg);
               break;
           case '/mi_id':
                TelegramEngine::enviarTexto($chatId, "Su Telegram Chat ID es: <code>{$chatId}</code>\nCópielo e ingréselo en el sistema.");
                break;
           case '/ventas_hoy':
               $stmt = $db->query("
                   SELECT 
                       COUNT(*) AS total_facturas,
                       COALESCE(SUM(total_general), 0) AS total_ventas_usd,
                       COALESCE(SUM(base_imponible), 0) AS base_usd
                   FROM ventas 
                   WHERE fecha_emision = CURDATE() AND estado = 'FACTURADA'
               ");
               $v = $stmt->fetch(PDO::FETCH_ASSOC);
               $msg = "📊 <b>RESUMEN DE VENTAS DE HOY (" . date('d/m/Y') . ")</b>\n\n"
                    . "🧾 <b>Facturas Emitidas:</b> {$v['total_facturas']}\n"
                    . "💵 <b>Total Facturado:</b> $" . number_format($v['total_ventas_usd'], 2) . "\n"
                    . "📈 <b>Base Imponible:</b> $" . number_format($v['base_usd'], 2);
               TelegramEngine::enviarTexto($chatId, $msg);
               break;
           case '/stock_critico':
               $stmt = $db->query("
                   SELECT p.codigo, p.descripcion, pd.existencia, pd.punto_reorden
                   FROM producto_deposito pd
                   INNER JOIN productos p ON pd.producto_id = p.id
                   WHERE pd.existencia <= pd.punto_reorden AND p.estado = 1
                   LIMIT 10
               ");
               $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
               if (empty($items)) {
                   TelegramEngine::enviarTexto($chatId, "✅ <b>Todo en orden:</b> No hay productos en nivel crítico.");
               } else {
                   $msg = "⚠️ <b>PRODUCTOS EN STOCK CRÍTICO:</b>\n\n";
                    foreach ($items as $it) {
                        $msg .= "• <code>{$it['codigo']}</code> - {$it['descripcion']}\n  Existencia: <b>{$it['existencia']}</b> (Mín: {$it['punto_reorden']})\n";
                    }
                    TelegramEngine::enviarTexto($chatId, $msg);
                }
                break;
            case '/bancos':
                $stmt = $db->query("SELECT nombre_banco, numero_cuenta, saldo_actual FROM cuentas_bancarias WHERE estado = 1");
                $bancos = $stmt->fetchAll(PDO::FETCH_ASSOC);
               $msg = "💰 <b>DISPONIBILIDAD EN BANCOS / CAJAS:</b>\n\n";
               $total = 0.0;
               foreach ($bancos as $b) {

                   $saldo = (float)$b['saldo_actual'];
                   $total += $saldo;
                   $msg .= "🏦 <b>{$b['nombre_banco']}</b>: $" . number_format($saldo, 2) . "\n";
               }
               $msg .= "\n💵 <b>Total Tesorería:</b> $" . number_format($total, 2);
               TelegramEngine::enviarTexto($chatId, $msg);
               break;
            default:
                TelegramEngine::enviarTexto($chatId, "Comando no reconocido. Escriba /start para ver las opciones disponibles.");
                break;
        }
    }

    private function procesarCallback(string $chatId, string $data, string $callbackQueryId): void {
        $db = Database::getConnection();
        // Ejemplo: Desbloqueo de límite de crédito remoto
        if (str_starts_with($data, 'aprobar_credito:')) {
            $clienteId = (int)explode(':', $data)[1];
            $db->prepare("UPDATE clientes SET limite_credito = limite_credito + 500 WHERE id = :id")->execute(['id' => $clienteId]);
            TelegramEngine::enviarTexto($chatId, "✅ <b>Crédito Aprobado:</b> Se incrementó el cupo del cliente ID {$clienteId}.");
        }
    }
}