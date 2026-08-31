-- 1. Configuración del Bot de Telegram
CREATE TABLE IF NOT EXISTS `telegram_config` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `bot_token` VARCHAR(150) NOT NULL, -- Token provisto por @BotFather (Ej: 123456:ABC-DEF...)
   `bot_username` VARCHAR(80) DEFAULT NULL, -- Ej: MiEmpresaAdminBot
   `canal_gerencial_chat_id` VARCHAR(50) DEFAULT NULL, -- ID del grupo o chat privado de directores (-100xxxxxxx)
   `alertas_stock_activo` TINYINT(1) DEFAULT 1,
   `alertas_anulaciones_activo` TINYINT(1) DEFAULT 1,
   `alertas_cierre_caja_activo` TINYINT(1) DEFAULT 1,
   `webhook_secret_token` VARCHAR(80) DEFAULT NULL,
   `estado` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Vinculación de Clientes y Usuarios con su Chat ID de Telegram
ALTER TABLE `clientes`
ADD COLUMN `telegram_chat_id` VARCHAR(50) DEFAULT NULL AFTER `email`;
ALTER TABLE `usuarios`
ADD COLUMN `telegram_chat_id` VARCHAR(50) DEFAULT NULL AFTER `rol_id`;
-- 3. Histórico de mensajes despachados por Telegram
CREATE TABLE IF NOT EXISTS `telegram_mensajes_log` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `chat_id` VARCHAR(50) NOT NULL,
   `tipo_mensaje` ENUM('NOTIFICACION_CLIENTE', 'ALERTA_GERENCIAL', 'COMANDO_BOT') NOT NULL,
   `mensaje_texto` TEXT NOT NULL,
   `documento_pdf_url` VARCHAR(255) DEFAULT NULL,
   `estado` ENUM('ENVIADO', 'ERROR') NOT NULL,
   `telegram_message_id` BIGINT DEFAULT NULL,
   `error_detalle` TEXT DEFAULT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `telegram_config` (`id`, `bot_token`, `estado`) 
VALUES (1, '', 0) 
ON DUPLICATE KEY UPDATE `id`=`id`;
