-- 1. Plan Único de Cuentas (PUC)
CREATE TABLE IF NOT EXISTS `contabilidad_plan_cuentas` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(30) NOT NULL UNIQUE, -- E.g. 1.1.01.01.001
   `descripcion` VARCHAR(150) NOT NULL,
   `tipo_cuenta` ENUM('ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESOS', 'COSTOS', 'GASTOS', 'ORDEN') NOT NULL,
   `naturaleza` ENUM('DEUDORA', 'ACREEDORA') NOT NULL,
   `nivel` TINYINT UNSIGNED NOT NULL DEFAULT 1,
   `acepta_movimiento` TINYINT(1) DEFAULT 1, -- 0 si es cuenta totalizadora/padre
   `cuenta_padre_id` INT UNSIGNED DEFAULT NULL,
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`cuenta_padre_id`) REFERENCES `contabilidad_plan_cuentas`(`id`) ON DELETE RESTRICT,
   INDEX `idx_codigo_cuenta` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Matriz de Mapeo de Cuentas Contables por Defecto
CREATE TABLE IF NOT EXISTS `contabilidad_mapeo_enlace` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `concepto` VARCHAR(60) NOT NULL UNIQUE,
    `cuenta_id` INT UNSIGNED NOT NULL,
    `descripcion` VARCHAR(120) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`cuenta_id`) REFERENCES `contabilidad_plan_cuentas`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Comprobantes de Diario (Cabecera)
CREATE TABLE IF NOT EXISTS `contabilidad_comprobantes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero_comprobante` VARCHAR(50) NOT NULL UNIQUE,
    `fecha` DATE NOT NULL,
    `tipo_origen` ENUM('MANUAL', 'VENTA', 'COMPRA', 'COBRO_CXC', 'PAGO_CXP', 'COSTO_VENTA', 'AJUSTE_INVENTARIO', 'CIERRE_NOMINA', 'DEPRECIACION') NOT NULL DEFAULT 'MANUAL',
   `documento_origen_id` BIGINT UNSIGNED DEFAULT NULL,
   `concepto_general` VARCHAR(255) NOT NULL,
   `total_debe` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `total_haber` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `moneda_id` INT UNSIGNED NOT NULL DEFAULT 1,
   `tasa_cambio` DECIMAL(18, 6) NOT NULL DEFAULT 1.000000,
   `estado` ENUM('BORRADOR', 'ASENTADO', 'ANULADO') DEFAULT 'ASENTADO',
   `usuario_id` INT UNSIGNED NOT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 4. Asientos / Renglones del Comprobante (Partida Doble)
CREATE TABLE IF NOT EXISTS `contabilidad_asientos_detalles` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `comprobante_id` BIGINT UNSIGNED NOT NULL,
   `cuenta_id` INT UNSIGNED NOT NULL,
   `referencia` VARCHAR(50) DEFAULT NULL,
   `descripcion_renglon` VARCHAR(200) NOT NULL,
   `debe` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `haber` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   FOREIGN KEY (`comprobante_id`) REFERENCES `contabilidad_comprobantes`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`cuenta_id`) REFERENCES `contabilidad_plan_cuentas`(`id`) ON DELETE RESTRICT,
   INDEX `idx_asiento_cuenta` (`cuenta_id`, `comprobante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Semillas del Plan de Cuentas Básico y Mapeo
INSERT INTO `contabilidad_plan_cuentas` (`id`, `codigo`, `descripcion`, `tipo_cuenta`, `naturaleza`, `nivel`, `acepta_movimiento`) VALUES
(1, '1', 'ACTIVO', 'ACTIVO', 'DEUDORA', 1, 0),
(2, '1.1', 'ACTIVO CORRIENTE', 'ACTIVO', 'DEUDORA', 2, 0),
(3, '1.1.01', 'DISPONIBILIDADES', 'ACTIVO', 'DEUDORA', 3, 0),
(4, '1.1.01.01', 'Caja General / POS', 'ACTIVO', 'DEUDORA', 4, 1),
(5, '1.1.01.02', 'Bancos Nacionales', 'ACTIVO', 'DEUDORA', 4, 1),
(6, '1.1.02.01', 'Cuentas por Cobrar Comerciales', 'ACTIVO', 'DEUDORA', 4, 1),
(7, '1.1.03.01', 'Inventario de Mercancías', 'ACTIVO', 'DEUDORA', 4, 1),
(8, '1.1.04.01', 'Crédito Fiscal IVA', 'ACTIVO', 'DEUDORA', 4, 1),
(9, '2', 'PASIVO', 'PASIVO', 'ACREEDORA', 1, 0),
(10, '2.1', 'PASIVO CORRIENTE', 'PASIVO', 'ACREEDORA', 2, 0),
(11, '2.1.01.01', 'Cuentas por Pagar Proveedores', 'PASIVO', 'ACREEDORA', 4, 1),
(12, '2.1.02.01', 'Débito Fiscal IVA por Pagar', 'PASIVO', 'ACREEDORA', 4, 1),
(13, '2.1.02.02', 'Retenciones de IVA por Enterar', 'PASIVO', 'ACREEDORA', 4, 1),
(14, '2.1.02.03', 'Retenciones de ISLR por Enterar', 'PASIVO', 'ACREEDORA', 4, 1),
(15, '4', 'INGRESOS', 'INGRESOS', 'ACREEDORA', 1, 0),
(16, '4.1.01.01', 'Ventas de Mercancía', 'INGRESOS', 'ACREEDORA', 4, 1),
(17, '5', 'COSTOS DE VENTA', 'COSTOS', 'DEUDORA', 1, 0),
(18, '5.1.01.01', 'Costo de Ventas de Mercancía', 'COSTOS', 'DEUDORA', 4, 1)
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;
INSERT INTO `contabilidad_mapeo_enlace` (`concepto`, `cuenta_id`, `descripcion`) VALUES
('CAJA_EFECTIVO', 4, 'Cuenta de caja general y terminales POS'),
('BANCOS', 5, 'Cuenta de operaciones bancarias'),
('CXC_CLIENTES', 6, 'Cuentas por cobrar comerciales clientes'),
('INVENTARIO_MERCANCIA', 7, 'Cuenta de existencias físicas de inventario'),
('IVA_CREDITO', 8, 'Crédito fiscal generado en compras'),
('CXP_PROVEEDORES', 11, 'Cuentas por pagar a proveedores comerciales'),
('IVA_DEBITO', 12, 'Débito fiscal generado en facturación de ventas'),
('RET_IVA_POR_PAGAR', 13, 'Retenciones de IVA aplicadas a proveedores'),
('RET_ISLR_POR_PAGAR', 14, 'Retenciones de ISLR aplicadas'),
('VENTAS_INGRESOS', 16, 'Ingresos netos por ventas comerciales'),
('COSTO_VENTAS', 18, 'Costo de la mercancía vendida')
ON DUPLICATE KEY UPDATE `concepto`=`concepto`;