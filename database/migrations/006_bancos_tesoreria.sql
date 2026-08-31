CREATE TABLE IF NOT EXISTS `cuentas_bancarias` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `tipo` ENUM('BANCO', 'CAJA_EFECTIVO', 'CAJA_POS') NOT NULL DEFAULT 'BANCO',
   `codigo` VARCHAR(20) NOT NULL UNIQUE,
   `nombre_banco` VARCHAR(100) NOT NULL,
   `numero_cuenta` VARCHAR(50) DEFAULT NULL,
   `moneda_id` INT UNSIGNED NOT NULL,
   `saldo_actual` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `saldo_conciliado` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`moneda_id`) REFERENCES `monedas`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `movimientos_bancarios` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `cuenta_id` INT UNSIGNED NOT NULL,
   `tipo_movimiento` ENUM('DEPOSITO', 'RETIRO', 'TRANSFERENCIA_SALIDA', 'TRANSFERENCIA_ENTRADA', 'COBRO_CLIENTE', 'PAGO_PROVEEDOR', 'AJUSTE') NOT NULL,
   `numero_referencia` VARCHAR(50) NOT NULL,
   `monto` DECIMAL(18, 4) NOT NULL,
   `tasa_cambio` DECIMAL(18, 6) NOT NULL DEFAULT 1.000000,
   `fecha` DATE NOT NULL,
   `conciliado` TINYINT(1) DEFAULT 0,
   `fecha_conciliacion` DATE DEFAULT NULL,
   `beneficiario_concepto` VARCHAR(255) NOT NULL,
   `usuario_id` INT UNSIGNED NOT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas_bancarias`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `recibos_cobranza` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `numero_recibo` VARCHAR(50) NOT NULL UNIQUE,
   `cliente_id` INT UNSIGNED NOT NULL,
   `fecha` DATE NOT NULL,
   `moneda_id` INT UNSIGNED NOT NULL,
   `tasa_cambio` DECIMAL(18, 6) NOT NULL,
   `monto_total` DECIMAL(18, 4) NOT NULL,
   `usuario_id` INT UNSIGNED NOT NULL,
   `nota` VARCHAR(255) DEFAULT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `recibos_cobranza_detalles` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `recibo_id` BIGINT UNSIGNED NOT NULL,
   `venta_id` BIGINT UNSIGNED NOT NULL,
   `monto_abonado` DECIMAL(18, 4) NOT NULL,
   FOREIGN KEY (`recibo_id`) REFERENCES `recibos_cobranza`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `recibos_cobranza_formas_pago` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `recibo_id` BIGINT UNSIGNED NOT NULL,
   `cuenta_id` INT UNSIGNED NOT NULL,
   `forma_pago` ENUM('EFECTIVO', 'TRANSFERENCIA', 'PUNTO_VENTA', 'PAGO_MOVIL', 'ZELLE', 'OTRO') NOT NULL,
   `referencia` VARCHAR(50) DEFAULT NULL,
   `monto` DECIMAL(18, 4) NOT NULL,
   FOREIGN KEY (`recibo_id`) REFERENCES `recibos_cobranza`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas_bancarias`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;