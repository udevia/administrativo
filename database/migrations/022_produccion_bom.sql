-- Módulo de Producción y Fórmulas de Fabricación (BOM)
CREATE TABLE IF NOT EXISTS `produccion_formulas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo_formula` VARCHAR(50) NOT NULL UNIQUE,
    `producto_terminado_id` INT UNSIGNED NOT NULL,
    `descripcion` VARCHAR(150) NOT NULL,
    `cantidad_producir_base` DECIMAL(14, 4) NOT NULL DEFAULT 1.0000,
    `costo_mano_obra_estimado` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `costo_indirecto_estimado` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `estado` ENUM('ACTIVA', 'INACTIVA') DEFAULT 'ACTIVA',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`producto_terminado_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `produccion_formulas_detalles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `formula_id` INT UNSIGNED NOT NULL,
    `insumo_producto_id` INT UNSIGNED NOT NULL,
    `cantidad_necesaria` DECIMAL(14, 4) NOT NULL,
    `porcentaje_merma_esperada` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (`formula_id`) REFERENCES `produccion_formulas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`insumo_producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `produccion_ordenes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero_orden` VARCHAR(50) NOT NULL UNIQUE,
    `formula_id` INT UNSIGNED NOT NULL,
    `deposito_materia_prima_id` INT UNSIGNED NOT NULL,
    `deposito_producto_terminado_id` INT UNSIGNED NOT NULL,
    `cantidad_planificada` DECIMAL(14, 4) NOT NULL,
    `cantidad_fabricada_real` DECIMAL(14, 4) NOT NULL,
    `costo_total_materia_prima` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `costo_total_mano_obra` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `costo_total_indirecto` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `costo_unitario_terminado` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `fecha_emision` DATE NOT NULL,
    `fecha_finalizacion` DATE DEFAULT NULL,
    `estado` ENUM('PLANIFICADA', 'EN_PROCESO', 'TERMINADA', 'ANULADA') DEFAULT 'PLANIFICADA',
    `usuario_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`formula_id`) REFERENCES `produccion_formulas`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`deposito_materia_prima_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`deposito_producto_terminado_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
