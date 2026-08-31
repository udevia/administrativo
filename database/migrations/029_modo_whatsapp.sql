ALTER TABLE `notificaciones_config`
ADD COLUMN `modo_whatsapp` ENUM('DESKTOP_WEB_GRATIS', 'META_CLOUD_API', 'DESACTIVADO') DEFAULT 'DESKTOP_WEB_GRATIS' AFTER `canal_whatsapp_activo`;