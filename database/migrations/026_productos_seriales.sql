-- 1. Garantía en la ficha maestra del producto
ALTER TABLE `productos`
ADD COLUMN `dias_garantia` SMALLINT UNSIGNED DEFAULT 90 AFTER `maneja_seriales`;

-- 2. Tabla Maestra de Seriales por Producto
CREATE TABLE IF NOT EXISTS `producto_seriales` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT UNSIGNED NOT NULL,
    `deposito_id` INT UNSIGNED NOT NULL,
    `numero_serial` VARCHAR(100) NOT NULL, -- Serial, IMEI 1, IMEI 2, Service Tag
    `estado` ENUM('DISPONIBLE', 'COMPROMETIDO', 'VENDIDO', 'DEVUELTO_PROVEEDOR', 'GARANTIA_RMA', 'DE_BAJA') DEFAULT 'DISPONIBLE',
    `costo_unitario_compra` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    
    -- Trazabilidad de Origen (Compra / Entrada)
    `compra_id` BIGINT UNSIGNED DEFAULT NULL,
    `fecha_ingreso` DATE NOT NULL,
    
    -- Trazabilidad de Destino (Venta / Salida / Garantía)
    `venta_id` BIGINT UNSIGNED DEFAULT NULL,
    `cliente_id` INT UNSIGNED DEFAULT NULL,
    `fecha_venta` DATE DEFAULT NULL,
    `fecha_vencimiento_garantia` DATE DEFAULT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`deposito_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `uk_prod_serial` (`producto_id`, `numero_serial`),
    INDEX `idx_serial_busqueda` (`numero_serial`),
    INDEX `idx_serial_estado` (`producto_id`, `deposito_id`, `estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Renglones de Factura y Compra vinculados a Seriales Específicos
CREATE TABLE IF NOT EXISTS `ventas_detalles_seriales` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `venta_detalle_id` BIGINT UNSIGNED NOT NULL,
    `serial_id` BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (`venta_detalle_id`) REFERENCES `ventas_detalles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`serial_id`) REFERENCES `producto_seriales`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `compras_detalles_seriales` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `compra_detalle_id` BIGINT UNSIGNED NOT NULL,
    `serial_id` BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (`compra_detalle_id`) REFERENCES `compras_detalles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`serial_id`) REFERENCES `producto_seriales`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;