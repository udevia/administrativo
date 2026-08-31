-- 1. Credenciales y configuración de APIs Bancarias
CREATE TABLE IF NOT EXISTS `pasarelas_bancarias_config` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `banco_codigo` VARCHAR(10) NOT NULL UNIQUE, -- '0134' (Banesco), '0138' (Banco Plaza)
   `nombre_banco` VARCHAR(80) NOT NULL,
   `tipo_integracion` ENUM('BANESCO_OPEN_BANKING', 'BANCO_PLAZA_API', 'GENERICO_SUDEBAN') NOT NULL,
   `api_url_base` VARCHAR(255) NOT NULL,
   `client_id` VARCHAR(120) NOT NULL,
   `client_secret` VARCHAR(255) NOT NULL,
   `api_key` VARCHAR(255) DEFAULT NULL,
   `comercio_rif` VARCHAR(25) NOT NULL,
   `comercio_telefono` VARCHAR(20) NOT NULL,
   `cuenta_bancaria_id` INT UNSIGNED NOT NULL, -- Cuenta de tesorería donde ingresan los fondos
   `ambiente` ENUM('SANDBOX', 'PRODUCCION') DEFAULT 'SANDBOX',
   `estado` TINYINT(1) DEFAULT 1,
   FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Bitácora de transacciones bancarias validadas
CREATE TABLE IF NOT EXISTS `pasarelas_transacciones_log` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

   `pedido_web_id` BIGINT UNSIGNED NOT NULL,
   `banco_codigo` VARCHAR(10) NOT NULL,
   `tipo_operacion` ENUM('C2P_DEBITO_INMEDIATO', 'VALIDACION_P2C_PAGOMOVIL') NOT NULL,
   `telefono_pagador` VARCHAR(20) NOT NULL,
   `cedula_pagador` VARCHAR(20) NOT NULL,
   `referencia_bancaria` VARCHAR(60) NOT NULL,
   `monto_bs` DECIMAL(18, 4) NOT NULL,
   `codigo_autorizacion` VARCHAR(60) DEFAULT NULL,
   `codigo_respuesta` VARCHAR(20) NOT NULL,
   `mensaje_respuesta` VARCHAR(255) NOT NULL,
   `raw_payload_request` LONGTEXT DEFAULT NULL,
   `raw_payload_response` LONGTEXT DEFAULT NULL,
   `estado` ENUM('APROBADO', 'RECHAZADO', 'ERROR') NOT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`pedido_web_id`) REFERENCES `ecommerce_pedidos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `pasarelas_bancarias_config` 
(`id`, `banco_codigo`, `nombre_banco`, `tipo_integracion`, `api_url_base`, `client_id`, `client_secret`, `comercio_rif`, `comercio_telefono`, `cuenta_bancaria_id`, `ambiente`, `estado`)
VALUES
(1, '0134', 'Banesco Banco Universal', 'BANESCO_OPEN_BANKING', 'https://api.banesco.com/c2p/v1', 'BANESCO_CLIENT_ID', 'BANESCO_SECRET', 'J-12345678-0', '04141234567', 1, 'SANDBOX', 1),
(2, '0138', 'Banco Plaza', 'BANCO_PLAZA_API', 'https://api.bancoplaza.com/c2p/v1', 'PLAZA_CLIENT_ID', 'PLAZA_SECRET', 'J-12345678-0', '04141234567', 1, 'SANDBOX', 1)
ON DUPLICATE KEY UPDATE `nombre_banco`=VALUES(`nombre_banco`);