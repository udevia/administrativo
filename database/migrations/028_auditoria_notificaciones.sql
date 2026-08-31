-- 1. Bitácora de Auditoría Forense e Histórico de Cambios
CREATE TABLE IF NOT EXISTS `auditoria_logs` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `usuario_id` INT UNSIGNED DEFAULT NULL,
   `supervisor_id` INT UNSIGNED DEFAULT NULL, -- Si la acción requirió desbloqueo/override
   `modulo` VARCHAR(50) NOT NULL, -- 'VENTAS', 'INVENTARIO', 'CLIENTES', 'CONFIGURACION', 'AUTH'
   `evento` VARCHAR(80) NOT NULL, -- 'CAMBIO_PRECIO', 'ANULACION_DOCUMENTO', 'LOGIN_FALLIDO', 'MODIFICACION_CREDITO'
   `tabla_afectada` VARCHAR(64) DEFAULT NULL,
   `registro_id` VARCHAR(64) DEFAULT NULL,
   `descripcion` TEXT NOT NULL,
   `payload_anterior` JSON DEFAULT NULL,
   `payload_nuevo` JSON DEFAULT NULL,
   `ip_origen` VARCHAR(45) NOT NULL,
   `user_agent` VARCHAR(255) DEFAULT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   INDEX `idx_auditoria_usuario` (`usuario_id`, `created_at`),
   INDEX `idx_auditoria_modulo` (`modulo`, `evento`),
   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
   FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Configuración de Canales de Notificación (WhatsApp API & SMTP)
CREATE TABLE IF NOT EXISTS `notificaciones_config` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `canal_whatsapp_activo` TINYINT(1) DEFAULT 0,
   `whatsapp_provider` ENUM('META_CLOUD_API', 'WHATSAPP_WEB_GATEWAY') DEFAULT 'META_CLOUD_API',
   `whatsapp_phone_number_id` VARCHAR(100) DEFAULT NULL,
   `whatsapp_access_token` TEXT DEFAULT NULL,
   `canal_email_activo` TINYINT(1) DEFAULT 0,
   `smtp_host` VARCHAR(120) DEFAULT NULL,
   `smtp_port` SMALLINT UNSIGNED DEFAULT 587,
   `smtp_user` VARCHAR(120) DEFAULT NULL,
   `smtp_pass` VARCHAR(150) DEFAULT NULL,
   `smtp_encryption` ENUM('TLS', 'SSL', 'NONE') DEFAULT 'TLS',
   `email_remitente` VARCHAR(120) DEFAULT NULL,
   `nombre_remitente` VARCHAR(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 3. Cola de Mensajes y Notificaciones Despachadas
CREATE TABLE IF NOT EXISTS `notificaciones_cola` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `tipo_documento` VARCHAR(50) NOT NULL, -- 'FACTURA', 'RECIBO_SAT', 'RECIBO_NOMINA', 'PEDIDO_WEB'
   `documento_id` BIGINT UNSIGNED NOT NULL,
   `canal` ENUM('WHATSAPP', 'EMAIL') NOT NULL,
   `destinatario` VARCHAR(120) NOT NULL, -- Teléfono (+58...) o Correo electrónico
   `asunto` VARCHAR(200) DEFAULT NULL,
   `mensaje_cuerpo` TEXT NOT NULL,
   `archivo_adjunto_url` VARCHAR(255) DEFAULT NULL,
   `estado` ENUM('PENDIENTE', 'ENVIADO', 'FALLIDO') DEFAULT 'PENDIENTE',
   `intentos` TINYINT UNSIGNED DEFAULT 0,
   `respuesta_proveedor` TEXT DEFAULT NULL,
   `enviado_at` TIMESTAMP NULL DEFAULT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   INDEX `idx_notif_cola` (`estado`, `canal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `notificaciones_config` (`id`, `canal_whatsapp_activo`, `canal_email_activo`) 
VALUES (1, 0, 0) 
ON DUPLICATE KEY UPDATE `id`=`id`;