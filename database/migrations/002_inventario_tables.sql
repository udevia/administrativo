CREATE TABLE IF NOT EXISTS `departamentos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(20) NOT NULL UNIQUE,
    `descripcion` VARCHAR(100) NOT NULL,
    `estado` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categorias` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `departamento_id` INT UNSIGNED NOT NULL,
    `codigo` VARCHAR(20) NOT NULL UNIQUE,
    `descripcion` VARCHAR(100) NOT NULL,
    `estado` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `unidades_medida` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(10) NOT NULL UNIQUE,
    `nombre` VARCHAR(50) NOT NULL,
    `decimales` TINYINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `productos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) NOT NULL UNIQUE,
    `codigo_barra` VARCHAR(100) DEFAULT NULL,
    `descripcion` VARCHAR(255) NOT NULL,
    `categoria_id` INT UNSIGNED NOT NULL,
    `unidad_medida_id` INT UNSIGNED NOT NULL,
    `tipo` ENUM('producto', 'servicio', 'compuesto') DEFAULT 'producto',
    `maneja_lotes` TINYINT(1) DEFAULT 0,
    `maneja_seriales` TINYINT(1) DEFAULT 0,
    `costo_ultimo` DECIMAL(18, 4) DEFAULT 0.0000,
    `costo_promedio` DECIMAL(18, 4) DEFAULT 0.0000,
    `costo_reposicion` DECIMAL(18, 4) DEFAULT 0.0000,
    `utilidad_a` DECIMAL(5, 2) DEFAULT 30.00,
    `precio_a` DECIMAL(18, 4) DEFAULT 0.0000,
    `utilidad_b` DECIMAL(5, 2) DEFAULT 25.00,
    `precio_b` DECIMAL(18, 4) DEFAULT 0.0000,
    `utilidad_c` DECIMAL(5, 2) DEFAULT 20.00,
    `precio_c` DECIMAL(18, 4) DEFAULT 0.0000,
    `utilidad_d` DECIMAL(5, 2) DEFAULT 15.00,
    `precio_d` DECIMAL(18, 4) DEFAULT 0.0000,
    `porcentaje_iva` DECIMAL(5, 2) DEFAULT 16.00,
    `exento_iva` TINYINT(1) DEFAULT 0,
    `stock_minimo` DECIMAL(14, 4) DEFAULT 0.0000,
    `stock_maximo` DECIMAL(14, 4) DEFAULT 0.0000,
    `estado` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidades_medida`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `producto_deposito` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT UNSIGNED NOT NULL,
    `deposito_id` INT UNSIGNED NOT NULL,
    `existencia` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `existencia_comprometida` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `existencia_por_llegar` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `punto_reorden` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `ubicacion_pasillo` VARCHAR(50) DEFAULT NULL,
    UNIQUE KEY `uk_prod_dep` (`producto_id`, `deposito_id`),
    FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`deposito_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kardex_inventario` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT UNSIGNED NOT NULL,
    `deposito_id` INT UNSIGNED NOT NULL,
    `tipo_movimiento` ENUM('ENTRADA_COMPRA', 'SALIDA_VENTA', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO', 'TRASLADO_ORIGEN', 'TRASLADO_DESTINO') NOT NULL,
    `documento_tipo` VARCHAR(20) NOT NULL,
    `documento_numero` VARCHAR(50) NOT NULL,
    `cantidad` DECIMAL(14, 4) NOT NULL,
    `costo_unitario` DECIMAL(18, 4) NOT NULL,
    `existencia_anterior` DECIMAL(14, 4) NOT NULL,
    `existencia_posterior` DECIMAL(14, 4) NOT NULL,
    `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `usuario_id` INT UNSIGNED NOT NULL,
    `nota` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`deposito_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
