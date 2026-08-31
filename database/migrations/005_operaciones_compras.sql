CREATE TABLE IF NOT EXISTS `compras` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `tipo_documento` ENUM('FACTURA_COMPRA', 'NOTA_RECEPCION', 'ORDEN_COMPRA', 'NOTA_DEBITO', 'NOTA_CREDITO') NOT NULL,
   `numero_factura` VARCHAR(50) NOT NULL,
   `numero_control` VARCHAR(50) DEFAULT NULL,
   
   `proveedor_id` INT UNSIGNED NOT NULL,
   `deposito_id` INT UNSIGNED NOT NULL,
   `usuario_id` INT UNSIGNED NOT NULL,
   
   `documento_origen_id` BIGINT UNSIGNED DEFAULT NULL,
   `recepcion_id` BIGINT UNSIGNED DEFAULT NULL,
   
   `fecha_emision` DATE NOT NULL,
   `fecha_recepcion` DATE NOT NULL,
   `fecha_vencimiento` DATE NOT NULL,
   `condicion_pago` ENUM('CONTADO', 'CREDITO') DEFAULT 'CONTADO',
   
   `moneda_id` INT UNSIGNED NOT NULL,
   `tasa_cambio` DECIMAL(18, 6) NOT NULL,
   
   `subtotal_neto` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `monto_exento` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `base_imponible` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `monto_iva` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   
   `retencion_iva_monto` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `retencion_islr_monto` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   
   `total_general` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `total_a_pagar` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000, -- Total general menos retenciones
   `saldo_pendiente` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,

   `estado` ENUM('PENDIENTE', 'PAGADA', 'ANULADA') DEFAULT 'PENDIENTE',
   `estado_flujo` ENUM('PENDIENTE', 'RECEPCION_PARCIAL', 'RECEPCION_TOTAL', 'FACTURADA', 'CANCELADA') DEFAULT 'PENDIENTE',
   `nota` TEXT DEFAULT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   
   FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`deposito_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `compras_detalles` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `compra_id` BIGINT UNSIGNED NOT NULL,
   `producto_id` INT UNSIGNED NOT NULL,
   `cantidad` DECIMAL(14, 4) NOT NULL,
   `costo_unitario` DECIMAL(18, 4) NOT NULL,
   `porcentaje_descuento` DECIMAL(5, 2) DEFAULT 0.00,
   `porcentaje_iva` DECIMAL(5, 2) NOT NULL,
   `subtotal` DECIMAL(18, 4) NOT NULL,
   `total` DECIMAL(18, 4) NOT NULL,
   
   FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cuentas_por_pagar` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `compra_id` BIGINT UNSIGNED NOT NULL,
   `proveedor_id` INT UNSIGNED NOT NULL,
   `tipo_transaccion` ENUM('CARGO_FACTURA', 'ABONO_PAGO', 'RETENCION_IVA', 'RETENCION_ISLR', 'AJUSTE') NOT NULL,
   `numero_referencia` VARCHAR(50) NOT NULL,
   `monto` DECIMAL(18, 4) NOT NULL,
   `fecha` DATE NOT NULL,
   `usuario_id` INT UNSIGNED NOT NULL,
   `nota` VARCHAR(255) DEFAULT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   
   FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;