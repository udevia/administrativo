CREATE TABLE IF NOT EXISTS `zonas` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(20) NOT NULL UNIQUE,
   `descripcion` VARCHAR(100) NOT NULL,
   `estado` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `vendedores` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(20) NOT NULL UNIQUE,
   `nombre` VARCHAR(120) NOT NULL,
   `cedula_rif` VARCHAR(25) NOT NULL UNIQUE,
   `telefono` VARCHAR(50),
   `email` VARCHAR(100),
   `comision_ventas` DECIMAL(5, 2) DEFAULT 0.00,
   `comision_cobros` DECIMAL(5, 2) DEFAULT 0.00,
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `clientes` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(30) NOT NULL UNIQUE,
   `razon_social` VARCHAR(150) NOT NULL,
   `nombre_comercial` VARCHAR(150) DEFAULT NULL,
   `documento_fiscal` VARCHAR(25) NOT NULL UNIQUE, -- RIF / NIT / Cédula
   `tipo_persona` ENUM('juridica', 'natural') DEFAULT 'juridica',

   `tipo_contribuyente` ENUM('ordinario', 'especial', 'formal', 'no_sujeto') DEFAULT 'ordinario',
   `direccion_fiscal` TEXT NOT NULL,
   `direccion_despacho` TEXT DEFAULT NULL,
   `telefono` VARCHAR(50),
   `email` VARCHAR(100),
   
   `zona_id` INT UNSIGNED NOT NULL,
   `vendedor_id` INT UNSIGNED DEFAULT NULL,
   `lista_precio_default` ENUM('A', 'B', 'C', 'D') DEFAULT 'A',
   
   `limite_credito` DECIMAL(18, 4) DEFAULT 0.0000,
   `dias_credito` SMALLINT UNSIGNED DEFAULT 0,
   `saldo_actual` DECIMAL(18, 4) DEFAULT 0.0000,
   `permite_credito` TINYINT(1) DEFAULT 0,
   
   `aplica_retencion_iva` TINYINT(1) DEFAULT 0,
   `porcentaje_retencion_iva` DECIMAL(5, 2) DEFAULT 75.00, -- 75% o 100%
   `aplica_retencion_islr` TINYINT(1) DEFAULT 0,
   
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (`zona_id`) REFERENCES `zonas`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `proveedores` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(30) NOT NULL UNIQUE,
   `razon_social` VARCHAR(150) NOT NULL,
   `documento_fiscal` VARCHAR(25) NOT NULL UNIQUE,
   `tipo_persona` ENUM('juridica', 'natural') DEFAULT 'juridica',
   `tipo_contribuyente` ENUM('ordinario', 'especial', 'formal', 'no_sujeto') DEFAULT 'ordinario',
   `direccion_fiscal` TEXT NOT NULL,
   `telefono` VARCHAR(50),
   `email` VARCHAR(100),
   `persona_contacto` VARCHAR(100),
   
   `dias_credito` SMALLINT UNSIGNED DEFAULT 0,
   `saldo_actual` DECIMAL(18, 4) DEFAULT 0.0000,
   
   `retencion_islr_concepto` VARCHAR(100) DEFAULT NULL,
   `retencion_islr_porcentaje` DECIMAL(5, 2) DEFAULT 0.00,
   
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `zonas` (`id`, `codigo`, `descripcion`) VALUES (1, 'ZON-GEN', 'Zona Principal')
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;
INSERT INTO `vendedores` (`id`, `codigo`, `nombre`, `cedula_rif`) VALUES (1, 'VEND-DIR', 'Venta Directa / Oficina', 'V-00000000')
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;