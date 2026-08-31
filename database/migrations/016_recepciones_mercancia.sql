-- Circuito de Compras: Fase 2 - Recepción física en Almacén Virtual / Cuarentena
CREATE TABLE IF NOT EXISTS `recepciones_mercancia` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero_recepcion` VARCHAR(50) NOT NULL UNIQUE,
    `orden_compra_id` BIGINT UNSIGNED DEFAULT NULL,
    `proveedor_id` INT UNSIGNED NOT NULL,
    `deposito_virtual_id` INT UNSIGNED NOT NULL DEFAULT 2,
    `deposito_destino_final_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `guia_despacho_proveedor` VARCHAR(100) DEFAULT NULL,
    `fecha_recepcion` DATE NOT NULL,
    `usuario_almacen_id` INT UNSIGNED NOT NULL,
    `estado` ENUM('EN_CUARENTENA', 'PROCESADA_COMPRA', 'RECHAZADA') DEFAULT 'EN_CUARENTENA',
    `nota` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`deposito_virtual_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`deposito_destino_final_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`usuario_almacen_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recepciones_mercancia_detalles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `recepcion_id` BIGINT UNSIGNED NOT NULL,
    `producto_id` INT UNSIGNED NOT NULL,
    `cantidad_recibida` DECIMAL(14, 4) NOT NULL,
    `costo_pactado` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `lote` VARCHAR(50) DEFAULT NULL,
    `fecha_vencimiento` DATE DEFAULT NULL,
    FOREIGN KEY (`recepcion_id`) REFERENCES `recepciones_mercancia`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
