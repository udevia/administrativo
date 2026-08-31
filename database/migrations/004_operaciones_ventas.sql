CREATE TABLE IF NOT EXISTS `ventas` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tipo_documento` ENUM('FACTURA', 'NOTA_ENTREGA', 'PRESUPUESTO', 'PEDIDO', 'APARTADO', 'NOTA_CREDITO', 'NOTA_DEBITO', 'DEVOLUCION_VENTA') NOT NULL,
    `numero_documento` VARCHAR(50) NOT NULL,
    `control_fiscal` VARCHAR(50) DEFAULT NULL,
    
    `cliente_id` INT UNSIGNED NOT NULL,
    `vendedor_id` INT UNSIGNED NOT NULL,
    `deposito_id` INT UNSIGNED NOT NULL,
    `usuario_id` INT UNSIGNED NOT NULL,
    
    `documento_origen_id` BIGINT UNSIGNED DEFAULT NULL,
    `documento_origen_tipo` VARCHAR(30) DEFAULT NULL,
    
    `fecha_emision` DATE NOT NULL,
    `fecha_vencimiento` DATE NOT NULL,
    `condicion_pago` ENUM('CONTADO', 'CREDITO') DEFAULT 'CONTADO',
    
    `moneda_id` INT UNSIGNED NOT NULL,       -- Moneda del documento (USD / VES)
    `tasa_cambio` DECIMAL(18, 6) NOT NULL,    -- Tasa histórica al momento de facturar
    
    `subtotal_neto` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `monto_exento` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `base_imponible` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `monto_iva` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `monto_igtf` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `total_general` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `saldo_pendiente` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    
    `estado` ENUM('PENDIENTE', 'PAGADA', 'ANULADA') DEFAULT 'PENDIENTE',
    `estado_flujo` ENUM('PENDIENTE', 'PROCESADO_PARCIAL', 'PROCESADO_TOTAL', 'CANCELADO') DEFAULT 'PENDIENTE',
    `hka_cufe` VARCHAR(120) DEFAULT NULL,
    `hka_status` VARCHAR(50) DEFAULT NULL,
    `hka_qr_url` TEXT DEFAULT NULL,
    `nota` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_tipo_num` (`tipo_documento`, `numero_documento`),
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`deposito_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ventas_detalles` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `venta_id` BIGINT UNSIGNED NOT NULL,
   `producto_id` INT UNSIGNED NOT NULL,
   `cantidad` DECIMAL(14, 4) NOT NULL,
   `costo_unitario_historico` DECIMAL(18, 4) NOT NULL,
   `precio_unitario` DECIMAL(18, 4) NOT NULL,
   `porcentaje_descuento` DECIMAL(5, 2) DEFAULT 0.00,
   `porcentaje_iva` DECIMAL(5, 2) NOT NULL,
   `subtotal` DECIMAL(18, 4) NOT NULL,
   `total` DECIMAL(18, 4) NOT NULL,
   
   FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE CASCADE,

   FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cuentas_por_cobrar` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `venta_id` BIGINT UNSIGNED NOT NULL,
   `cliente_id` INT UNSIGNED NOT NULL,
   `tipo_transaccion` ENUM('CARGO_FACTURA', 'ABONO_PAGO', 'RETENCION_IVA', 'RETENCION_ISLR', 'AJUSTE') NOT NULL,
   `numero_referencia` VARCHAR(50) NOT NULL,
   `monto` DECIMAL(18, 4) NOT NULL,
   `fecha` DATE NOT NULL,
   `usuario_id` INT UNSIGNED NOT NULL,
   `nota` VARCHAR(255) DEFAULT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   
   FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;