-- 1. Categorías de Activos Fijos (Parámetros y Vidas Útiles Estándar)
CREATE TABLE IF NOT EXISTS `activos_categorias` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(20) NOT NULL UNIQUE,
   `nombre` VARCHAR(100) NOT NULL,
   `vida_util_meses_default` SMALLINT UNSIGNED NOT NULL DEFAULT 60, -- Ej: 5 años = 60 meses
   `porcentaje_residual_default` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
   `cuenta_activo_id` INT UNSIGNED NOT NULL, -- 1.2.01 (Activo No Corriente)
   `cuenta_depreciacion_acumulada_id` INT UNSIGNED NOT NULL, -- 1.2.02 (Activo Contracuenta / Acreedora)
   `cuenta_gasto_depreciacion_id` INT UNSIGNED NOT NULL, -- 6.1.02 (Gasto Operacional)
   `estado` TINYINT(1) DEFAULT 1,
   FOREIGN KEY (`cuenta_activo_id`) REFERENCES `contabilidad_plan_cuentas`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`cuenta_depreciacion_acumulada_id`) REFERENCES `contabilidad_plan_cuentas`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`cuenta_gasto_depreciacion_id`) REFERENCES `contabilidad_plan_cuentas`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Ficha Maestra de Activos Fijos
CREATE TABLE IF NOT EXISTS `activos_fijos` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo_placa_activo` VARCHAR(50) NOT NULL UNIQUE, -- Código de barra / Placa interna
   `categoria_id` INT UNSIGNED NOT NULL,
   `departamento_id` INT UNSIGNED NOT NULL, -- Ubicación física / Centro de costo
   `responsable_usuario_id` INT UNSIGNED DEFAULT NULL, -- Custodio asignado
   `descripcion` VARCHAR(200) NOT NULL,
   `marca_modelo` VARCHAR(100) DEFAULT NULL,
   `numero_serial` VARCHAR(100) DEFAULT NULL,
   `fecha_adquisicion` DATE NOT NULL,
   `fecha_inicio_depreciacion` DATE NOT NULL,
   `numero_factura_compra` VARCHAR(50) DEFAULT NULL,
   `proveedor_id` INT UNSIGNED DEFAULT NULL,
   
   -- Valores Financieros
   `costo_adquisicion_usd` DECIMAL(18, 4) NOT NULL,
   `tasa_cambio_adquisicion` DECIMAL(18, 6) NOT NULL DEFAULT 1.000000,
   `valor_residual_usd` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `vida_util_meses` SMALLINT UNSIGNED NOT NULL,
   `meses_depreciados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
   `depreciacion_acumulada_usd` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `valor_en_libros_usd` DECIMAL(18, 4) NOT NULL,
   
   `metodo_depreciacion` ENUM('LINEA_RECTA', 'SUMA_DIGITOS', 'NO_DEPRECIA') DEFAULT 'LINEA_RECTA',
   `estado` ENUM('ACTIVO', 'TOTALMENTE_DEPRECIADO', 'EN_MANTENIMIENTO', 'DESINCORPORADO_BAJA', 'VENDIDO') DEFAULT 'ACTIVO',
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`categoria_id`) REFERENCES `activos_categorias`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
   FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE SET NULL,
   INDEX `idx_activo_codigo` (`codigo_placa_activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 3. Histórico y Tabla de Amortización / Depreciación Mensual
CREATE TABLE IF NOT EXISTS `activos_depreciaciones_mensuales` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `activo_id` BIGINT UNSIGNED NOT NULL,
   `ano` SMALLINT UNSIGNED NOT NULL,
   `mes` TINYINT UNSIGNED NOT NULL,
   `monto_depreciacion_mes_usd` DECIMAL(18, 4) NOT NULL,
   `depreciacion_acumulada_momento_usd` DECIMAL(18, 4) NOT NULL,
   `valor_libros_momento_usd` DECIMAL(18, 4) NOT NULL,

   `comprobante_contable_id` BIGINT UNSIGNED DEFAULT NULL,
   `fecha_calculo` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   UNIQUE KEY `uk_activo_periodo` (`activo_id`, `ano`, `mes`),
   FOREIGN KEY (`activo_id`) REFERENCES `activos_fijos`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`comprobante_contable_id`) REFERENCES `contabilidad_comprobantes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Mapeo de Cuentas Contables y Categorías Base
INSERT INTO `contabilidad_plan_cuentas` (`id`, `codigo`, `descripcion`, `tipo_cuenta`, `naturaleza`, `nivel`, `acepta_movimiento`) VALUES
(26, '1.2', 'ACTIVO NO CORRIENTE / INMOBILIZADO', 'ACTIVO', 'DEUDORA', 2, 0),
(27, '1.2.01.01', 'Mobiliario y Equipos de Oficina', 'ACTIVO', 'DEUDORA', 4, 1),
(28, '1.2.01.02', 'Equipos de Computación y Sistemas', 'ACTIVO', 'DEUDORA', 4, 1),
(29, '1.2.01.03', 'Vehículos y Transporte', 'ACTIVO', 'DEUDORA', 4, 1),
(30, '1.2.01.04', 'Maquinaria y Equipos Industriales', 'ACTIVO', 'DEUDORA', 4, 1),
(31, '1.2.02.01', 'Depreciación Acumulada Mobiliario', 'ACTIVO', 'ACREEDORA', 4, 1),
(32, '1.2.02.02', 'Depreciación Acumulada Equipos Computación', 'ACTIVO', 'ACREEDORA', 4, 1),
(33, '1.2.02.03', 'Depreciación Acumulada Vehículos', 'ACTIVO', 'ACREEDORA', 4, 1),
(34, '1.2.02.04', 'Depreciación Acumulada Maquinaria', 'ACTIVO', 'ACREEDORA', 4, 1),
(35, '6.1.02.01', 'Gasto Depreciación Mobiliario', 'GASTOS', 'DEUDORA', 4, 1),
(36, '6.1.02.02', 'Gasto Depreciación Computación', 'GASTOS', 'DEUDORA', 4, 1),
(37, '6.1.02.03', 'Gasto Depreciación Vehículos', 'GASTOS', 'DEUDORA', 4, 1),
(38, '6.1.02.04', 'Gasto Depreciación Maquinaria', 'GASTOS', 'DEUDORA', 4, 1)
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;

INSERT INTO `activos_categorias` (`id`, `codigo`, `nombre`, `vida_util_meses_default`, `cuenta_activo_id`, `cuenta_depreciacion_acumulada_id`, `cuenta_gasto_depreciacion_id`) VALUES
(1, 'EQUIPOS_COMP', 'Equipos de Computación y Servidores', 36, 28, 32, 36), -- 3 Años
(2, 'MOBILIARIO', 'Mobiliario y Enseres de Oficina', 60, 27, 31, 35), -- 5 Años
(3, 'VEHICULOS', 'Vehículos y Flota de Transporte', 60, 29, 33, 37), -- 5 Años
(4, 'MAQUINARIA', 'Maquinaria y Líneas de Producción', 120, 30, 34, 38) -- 10 Años
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;