-- 1. Configuración de la Tienda Online
CREATE TABLE IF NOT EXISTS `ecommerce_config` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `nombre_tienda` VARCHAR(100) NOT NULL,
   `slug_tienda` VARCHAR(80) NOT NULL UNIQUE,
   `deposito_despacho_id` INT UNSIGNED NOT NULL DEFAULT 1,
   `lista_precio_b2c_default` ENUM('A', 'B', 'C', 'D') DEFAULT 'A',
   `permitir_pedidos_sin_stock` TINYINT(1) DEFAULT 0,
   `requiere_registro_b2b` TINYINT(1) DEFAULT 0,
   `datos_pago_pago_movil` TEXT DEFAULT NULL, -- Banco, Teléfono, RIF
   `datos_pago_zelle` TEXT DEFAULT NULL, -- Correo, Titular
   `datos_pago_transferencia` TEXT DEFAULT NULL,
   `monto_minimo_despacho` DECIMAL(18, 4) DEFAULT 0.0000,
   `estado_tienda` ENUM('ACTIVA', 'MANTENIMIENTO') DEFAULT 'ACTIVA',
   FOREIGN KEY (`deposito_despacho_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Registro de Usuarios Web / Clientes B2C
CREATE TABLE IF NOT EXISTS `ecommerce_usuarios` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `cliente_id` INT UNSIGNED DEFAULT NULL, -- NULL si es B2C nuevo, o ID de clientes si es B2B vinculado
   `nombre_completo` VARCHAR(150) NOT NULL,
   `email` VARCHAR(100) NOT NULL UNIQUE,
   `password` VARCHAR(255) NOT NULL,
   `telefono` VARCHAR(30) NOT NULL,
   `documento_identidad` VARCHAR(25) NOT NULL,
   `direccion_entrega` TEXT NOT NULL,
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 3. Pedidos Web y Validación de Pagos
CREATE TABLE IF NOT EXISTS `ecommerce_pedidos` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `numero_orden_web` VARCHAR(50) NOT NULL UNIQUE,
   `usuario_web_id` BIGINT UNSIGNED NOT NULL,

   `venta_apartado_id` BIGINT UNSIGNED NOT NULL, -- ID del documento generado en la tabla ventas (APARTADO)
   `metodo_pago` ENUM('PAGO_MOVIL', 'ZELLE', 'TRANSFERENCIA_VES', 'CREDITO_B2B', 'EFECTIVO_CONTRAENTREGA') NOT NULL,
   `referencia_pago` VARCHAR(100) DEFAULT NULL,
   `comprobante_pago_url` VARCHAR(255) DEFAULT NULL,
   `monto_total_usd` DECIMAL(18, 4) NOT NULL,
   `tasa_cambio` DECIMAL(18, 6) NOT NULL,
   `monto_total_bs` DECIMAL(18, 4) NOT NULL,
   `estado_pago` ENUM('PENDIENTE_REVISION', 'VERIFICADO', 'RECHAZADO') DEFAULT 'PENDIENTE_REVISION',
   `estado_despacho` ENUM('RECIBIDO', 'EN_PREPARACION', 'ENVIADO', 'ENTREGADO', 'CANCELADO') DEFAULT 'RECIBIDO',
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`usuario_web_id`) REFERENCES `ecommerce_usuarios`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`venta_apartado_id`) REFERENCES `ventas`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Marcar productos que se publican en la web y fotos
ALTER TABLE `productos`
ADD COLUMN `publicar_en_ecommerce` TINYINT(1) DEFAULT 1 AFTER `estado`,
ADD COLUMN `imagen_url` VARCHAR(255) DEFAULT NULL AFTER `publicar_en_ecommerce`;
-- Configuración inicial
INSERT INTO `ecommerce_config` (`id`, `nombre_tienda`, `slug_tienda`, `deposito_despacho_id`, `lista_precio_b2c_default`, `datos_pago_pago_movil`, `datos_pago_zelle`)
VALUES (1, 'Mi Tienda Online', 'tienda', 1, 'A', 'Banesco (0134) - 0414-1234567 - J-12345678-0', 'pagos@mitienda.com')
ON DUPLICATE KEY UPDATE `id`=`id`;