-- 1. Matriz de comisiones por categoría / departamento
CREATE TABLE IF NOT EXISTS `vendedores_comisiones_reglas` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `vendedor_id` INT UNSIGNED NOT NULL,
   `categoria_id` INT UNSIGNED DEFAULT NULL, -- NULL aplica a todas las categorías
   `departamento_id` INT UNSIGNED DEFAULT NULL,
   `porcentaje_comision_venta` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
   `porcentaje_comision_cobro` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
   FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Historial y liquidación de comisiones acumuladas
CREATE TABLE IF NOT EXISTS `vendedores_comisiones_historico` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `vendedor_id` INT UNSIGNED NOT NULL,
   `tipo_origen` ENUM('VENTA_DIRECTA', 'COBRO_CXC') NOT NULL,
   `documento_id` BIGINT UNSIGNED NOT NULL,
   `numero_documento` VARCHAR(50) NOT NULL,
   `cliente_id` INT UNSIGNED NOT NULL,
   `base_calculo` DECIMAL(18, 4) NOT NULL,
   `porcentaje_aplicado` DECIMAL(5, 2) NOT NULL,
   `monto_comision` DECIMAL(18, 4) NOT NULL,
   `fecha_devengada` DATE NOT NULL,
   `estado_liquidacion` ENUM('PENDIENTE', 'PAGADA', 'ANULADA') DEFAULT 'PENDIENTE',
   `fecha_pago` DATE DEFAULT NULL,
   FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 3. Token de acceso para la PWA de preventa móvil
ALTER TABLE `vendedores`
ADD COLUMN `pin_acceso_movil` VARCHAR(6) DEFAULT NULL AFTER `cedula_rif`,
ADD COLUMN `token_sesion_movil` VARCHAR(64) DEFAULT NULL AFTER `pin_acceso_movil`,
ADD COLUMN `deposito_asignado_id` INT UNSIGNED DEFAULT 1 AFTER `token_sesion_movil`;