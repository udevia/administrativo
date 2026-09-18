-- ====================================================================
-- Migración 032: Tabla de Configuración KV, Consumos de Producción
--                y Tipos de Movimiento de Kardex para BOM
-- ====================================================================

-- 1. Configuración tipo clave/valor (canales, pasarelas, datos de empresa)
--    Referenciada por:
--      - GET/POST api/configuracion/canales (public/index.php)
--      - SELECT clave, valor FROM configuracion WHERE clave LIKE 'empresa_%'
--        (ComprasController, VentasController)
CREATE TABLE IF NOT EXISTS `configuracion` (
    `clave` VARCHAR(120) NOT NULL,
    `valor` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Consumos reales de materia prima por Orden de Producción (BOM)
--    Referenciada por:
--      - INSERT INTO produccion_ordenes_consumo (orden_id, material_id, cantidad_consumida, costo_unitario)
--      - SELECT SUM(cantidad_consumida * costo_unitario) ... WHERE orden_id = ?
--    (app/Models/ProduccionService.php)
CREATE TABLE IF NOT EXISTS `produccion_ordenes_consumo` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `orden_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL,
    `cantidad_consumida` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
    `costo_unitario` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_consumo_orden` (`orden_id`),
    INDEX `idx_consumo_material` (`material_id`),
    FOREIGN KEY (`orden_id`) REFERENCES `produccion_ordenes` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ampliación del ENUM de tipo de movimiento del kardex para el módulo
--    de producción (consumo de materia prima y entrada de producto terminado)
ALTER TABLE `kardex_inventario`
    MODIFY COLUMN `tipo_movimiento` ENUM(
        'ENTRADA_COMPRA',
        'SALIDA_VENTA',
        'AJUSTE_POSITIVO',
        'AJUSTE_NEGATIVO',
        'TRASLADO_ORIGEN',
        'TRASLADO_DESTINO',
        'SALIDA_PRODUCCION',
        'ENTRADA_PRODUCCION'
    ) NOT NULL;
