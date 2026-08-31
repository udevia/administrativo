-- 1. Ficha Maestra de Empleados
CREATE TABLE IF NOT EXISTS `nomina_empleados` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(20) NOT NULL UNIQUE,
   `cedula` VARCHAR(20) NOT NULL UNIQUE,
   `nombres` VARCHAR(100) NOT NULL,
   `apellidos` VARCHAR(100) NOT NULL,
   `cargo` VARCHAR(100) NOT NULL,
   `departamento_id` INT UNSIGNED DEFAULT 1,
   `fecha_ingreso` DATE NOT NULL,
   `frecuencia_pago` ENUM('SEMANAL', 'QUINCENAL', 'MENSUAL') DEFAULT 'QUINCENAL',
   `tipo_sueldo` ENUM('FIJO', 'POR_HORA', 'DESTAJO') DEFAULT 'FIJO',
   `sueldo_base_mensual` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `bono_alimentacion_mensual` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `banco_id` INT UNSIGNED DEFAULT NULL,
   `numero_cuenta_bancaria` VARCHAR(30) DEFAULT NULL,
   `aplica_ivss` TINYINT(1) DEFAULT 1,
   `aplica_faov` TINYINT(1) DEFAULT 1,
   `aplica_pie` TINYINT(1) DEFAULT 1,
   `estado` ENUM('ACTIVO', 'VACACIONES', 'SUSPENDIDO', 'LIQUIDADO') DEFAULT 'ACTIVO',
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`banco_id`) REFERENCES `cuentas_bancarias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Catálogo Maestro de Conceptos (Asignaciones y Deducciones)
CREATE TABLE IF NOT EXISTS `nomina_conceptos` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(20) NOT NULL UNIQUE,
   `descripcion` VARCHAR(100) NOT NULL,
   `tipo` ENUM('ASIGNACION', 'DEDUCCION', 'APORTE_PATRONAL') NOT NULL,
   `formula_o_fijo` ENUM('FIJO', 'PORCENTAJE', 'DIAS', 'HORAS') NOT NULL DEFAULT 'FIJO',
   `valor_default` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `es_ley` TINYINT(1) DEFAULT 0,
   `cuenta_contable_debe_id` INT UNSIGNED DEFAULT NULL,
   `cuenta_contable_haber_id` INT UNSIGNED DEFAULT NULL,
   `estado` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Cabecera del Período de Nómina (Procesamiento)
CREATE TABLE IF NOT EXISTS `nomina_periodos` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo_periodo` VARCHAR(50) NOT NULL UNIQUE,
   `frecuencia` ENUM('SEMANAL', 'QUINCENAL', 'MENSUAL') NOT NULL,
   `fecha_inicio` DATE NOT NULL,
   `fecha_fin` DATE NOT NULL,
   `fecha_pago` DATE NOT NULL,
   `total_asignaciones` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `total_deducciones` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `total_aportes_patronales` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `total_neto_a_pagar` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `estado` ENUM('PRENOMINA', 'CERRADA_PAGADA', 'ANULADA') DEFAULT 'PRENOMINA',
   `cuenta_bancaria_pago_id` INT UNSIGNED DEFAULT NULL,
   `movimiento_bancario_id` BIGINT UNSIGNED DEFAULT NULL,
   `comprobante_contable_id` BIGINT UNSIGNED DEFAULT NULL,
   `usuario_id` INT UNSIGNED NOT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`cuenta_bancaria_pago_id`) REFERENCES `cuentas_bancarias`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`movimiento_bancario_id`) REFERENCES `movimientos_bancarios`(`id`) ON DELETE SET NULL,
   FOREIGN KEY (`comprobante_contable_id`) REFERENCES `contabilidad_comprobantes`(`id`) ON DELETE SET NULL,
   FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Detalle de Recibo de Pago por Empleado
CREATE TABLE IF NOT EXISTS `nomina_recibos` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `periodo_id` BIGINT UNSIGNED NOT NULL,
   `empleado_id` INT UNSIGNED NOT NULL,
   `sueldo_base_periodo` DECIMAL(18, 4) NOT NULL,
   `total_asignaciones` DECIMAL(18, 4) NOT NULL,
   `total_deducciones` DECIMAL(18, 4) NOT NULL,
   `neto_cobrar` DECIMAL(18, 4) NOT NULL,
   `pago_procesado` TINYINT(1) DEFAULT 0,
   FOREIGN KEY (`periodo_id`) REFERENCES `nomina_periodos`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`empleado_id`) REFERENCES `nomina_empleados`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nomina_recibos_detalles` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `recibo_id` BIGINT UNSIGNED NOT NULL,
   `concepto_id` INT UNSIGNED NOT NULL,
   `descripcion` VARCHAR(100) NOT NULL,
   `tipo` ENUM('ASIGNACION', 'DEDUCCION', 'APORTE_PATRONAL') NOT NULL,
   `monto` DECIMAL(18, 4) NOT NULL,
   FOREIGN KEY (`recibo_id`) REFERENCES `nomina_recibos`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`concepto_id`) REFERENCES `nomina_conceptos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mapeo de Cuentas Contables y Conceptos de Ley
INSERT INTO `contabilidad_plan_cuentas` (`id`, `codigo`, `descripcion`, `tipo_cuenta`, `naturaleza`, `nivel`, `acepta_movimiento`) VALUES
(19, '2.1.03.01', 'Sueldos y Salarios por Pagar', 'PASIVO', 'ACREEDORA', 4, 1),
(20, '2.1.03.02', 'Retenciones IVSS por Enterar', 'PASIVO', 'ACREEDORA', 4, 1),
(21, '2.1.03.03', 'Retenciones FAOV por Enterar', 'PASIVO', 'ACREEDORA', 4, 1),
(22, '6', 'GASTOS DE PERSONAL', 'GASTOS', 'DEUDORA', 1, 0),
(23, '6.1.01.01', 'Gasto Sueldos y Salarios', 'GASTOS', 'DEUDORA', 4, 1),
(24, '6.1.01.02', 'Gasto Bono de Alimentación', 'GASTOS', 'DEUDORA', 4, 1),
(25, '6.1.01.03', 'Gasto Aportes Patronales (IVSS/FAOV/INCES)', 'GASTOS', 'DEUDORA', 4, 1)
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;

INSERT INTO `nomina_conceptos` (`id`, `codigo`, `descripcion`, `tipo`, `formula_o_fijo`, `valor_default`, `es_ley`, `cuenta_contable_debe_id`, `cuenta_contable_haber_id`) VALUES
(1, 'SUELDO_BASE', 'Sueldo Básico de Período', 'ASIGNACION', 'FIJO', 0.00, 1, 23, 19),
(2, 'BONO_ALIM', 'Bono de Alimentación (Cestaticket)', 'ASIGNACION', 'FIJO', 0.00, 1, 24, 19),
(3, 'DED_IVSS', 'Seguro Social Obligatorio (4%)', 'DEDUCCION', 'PORCENTAJE', 4.00, 1, 19, 20),
(4, 'DED_FAOV', 'Fondo Ahorro Obligatorio Vivienda (1%)', 'DEDUCCION', 'PORCENTAJE', 1.00, 1, 19, 21),
(5, 'DED_PIE', 'Pérdida Involuntaria de Empleo (0.5%)', 'DEDUCCION', 'PORCENTAJE', 0.50, 1, 19, 20),
(6, 'APO_IVSS', 'Aporte Patronal IVSS (Riesgo Medio 10%)', 'APORTE_PATRONAL', 'PORCENTAJE', 10.00, 1, 25, 20),
(7, 'APO_FAOV', 'Aporte Patronal FAOV (2%)', 'APORTE_PATRONAL', 'PORCENTAJE', 2.00, 1, 25, 21)
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;