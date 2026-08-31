CREATE TABLE IF NOT EXISTS `empresa` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `razon_social` VARCHAR(150) NOT NULL,
    `nombre_comercial` VARCHAR(150),
    `rif` VARCHAR(25) NOT NULL UNIQUE,
    `direccion_fiscal` TEXT NOT NULL,
    `telefono` VARCHAR(50),
    `email` VARCHAR(100),
    `moneda_base_id` INT UNSIGNED DEFAULT 1,
    `moneda_secundaria_id` INT UNSIGNED DEFAULT 2,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `monedas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(10) NOT NULL UNIQUE,
    `nombre` VARCHAR(50) NOT NULL,
    `simbolo` VARCHAR(5) NOT NULL,
    `es_base` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasas_cambio` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `moneda_id` INT UNSIGNED NOT NULL,
    `tasa` DECIMAL(18, 6) NOT NULL,
    `fecha` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`moneda_id`) REFERENCES `monedas`(`id`) ON DELETE CASCADE,
    INDEX `idx_fecha_moneda` (`fecha`, `moneda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `usuario` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('admin', 'supervisor', 'cajero', 'ventas', 'almacen') DEFAULT 'admin',
    `estado` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `depositos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(20) NOT NULL UNIQUE,
    `descripcion` VARCHAR(100) NOT NULL,
    `responsable` VARCHAR(100),
    `estado` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `monedas` (`id`, `codigo`, `nombre`, `simbolo`, `es_base`) VALUES
(1, 'VES', 'Bolívares', 'Bs.', 1),
(2, 'USD', 'Dólares Americanos', '$', 0)
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;

INSERT INTO `depositos` (`codigo`, `descripcion`) VALUES
('DEP-01', 'Depósito Principal')
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;
