CREATE TABLE IF NOT EXISTS `productos_presentaciones` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT UNSIGNED NOT NULL,
    `codigo_barra` VARCHAR(100) DEFAULT NULL,
    `nombre_presentacion` VARCHAR(50) NOT NULL,
    `factor_conversion` DECIMAL(14, 4) NOT NULL DEFAULT 1.0000,
    `es_unidad_base` TINYINT(1) DEFAULT 0,
    
    -- Configuración de precios por presentación
    `tipo_calculo_precio` ENUM('MANUAL', 'PROPORCIONAL_DIRECTO', 'MARGEN_FRACCION') DEFAULT 'PROPORCIONAL_DIRECTO',
    `porcentaje_recargo_fraccion` DECIMAL(5, 2) DEFAULT 0.00,
    
    -- Precios específicos para esta presentación
    `precio_a` DECIMAL(18, 4) DEFAULT 0.0000,
    `precio_b` DECIMAL(18, 4) DEFAULT 0.0000,
    `precio_c` DECIMAL(18, 4) DEFAULT 0.0000,
    `precio_d` DECIMAL(18, 4) DEFAULT 0.0000,
    
    `estado` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_prod_presentacion` (`producto_id`, `nombre_presentacion`),
    INDEX `idx_pres_barra` (`codigo_barra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modificamos los renglones de venta y compra para guardar la presentación usada
ALTER TABLE `ventas_detalles`
ADD COLUMN `presentacion_id` BIGINT UNSIGNED DEFAULT NULL AFTER `producto_id`,
ADD COLUMN `factor_conversion` DECIMAL(14, 4) NOT NULL DEFAULT 1.0000 AFTER `cantidad`,
ADD COLUMN `cantidad_unidad_base` DECIMAL(14, 4) NOT NULL DEFAULT 1.0000 AFTER `factor_conversion`,
ADD CONSTRAINT `fk_vd_presentacion` FOREIGN KEY (`presentacion_id`) REFERENCES `productos_presentaciones`(`id`) ON DELETE SET NULL;

ALTER TABLE `compras_detalles`
ADD COLUMN `presentacion_id` BIGINT UNSIGNED DEFAULT NULL AFTER `producto_id`,
ADD COLUMN `factor_conversion` DECIMAL(14, 4) NOT NULL DEFAULT 1.0000 AFTER `cantidad`,
ADD COLUMN `cantidad_unidad_base` DECIMAL(14, 4) NOT NULL DEFAULT 1.0000 AFTER `factor_conversion`,
ADD CONSTRAINT `fk_cd_presentacion` FOREIGN KEY (`presentacion_id`) REFERENCES `productos_presentaciones`(`id`) ON DELETE SET NULL;