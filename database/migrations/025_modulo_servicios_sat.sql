-- 1. Tipos de Servicio / Plantillas de Recepción (Ej: Automotriz, Celulares, Laptops, Maquinaria)
CREATE TABLE IF NOT EXISTS `sat_tipos_servicio` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo` VARCHAR(30) NOT NULL UNIQUE,
   `nombre` VARCHAR(100) NOT NULL,
   `prefijo_correlativo` VARCHAR(10) NOT NULL DEFAULT 'OT-',
   `dias_garantia_default` SMALLINT UNSIGNED DEFAULT 30,
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Campos Dinámicos Parametrizables por Tipo de Servicio (Metadatos configurables)
CREATE TABLE IF NOT EXISTS `sat_campos_personalizados` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `tipo_servicio_id` INT UNSIGNED NOT NULL,
   `nombre_campo` VARCHAR(60) NOT NULL,
   `slug` VARCHAR(60) NOT NULL,
   `tipo_dato` ENUM('TEXTO', 'NUMERO', 'FECHA', 'LISTA_SELECCION', 'CHECKBOX', 'TEXTO_LARGO') NOT NULL DEFAULT 'TEXTO',
   `opciones_lista` TEXT DEFAULT NULL,
   `es_obligatorio` TINYINT(1) DEFAULT 0,
   `orden` TINYINT UNSIGNED DEFAULT 1,
   FOREIGN KEY (`tipo_servicio_id`) REFERENCES `sat_tipos_servicio`(`id`) ON DELETE CASCADE,
   UNIQUE KEY `uk_tipo_slug` (`tipo_servicio_id`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 3. Estados del Flujo de Trabajo / Pipeline Parametrizable
CREATE TABLE IF NOT EXISTS `sat_estados_flujo` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `nombre` VARCHAR(50) NOT NULL,
   `slug` VARCHAR(50) NOT NULL UNIQUE,
   `color_hex` VARCHAR(10) NOT NULL DEFAULT '#3b82f6',
   `es_estado_inicial` TINYINT(1) DEFAULT 0,
   `es_estado_final` TINYINT(1) DEFAULT 0,
   `permite_facturar` TINYINT(1) DEFAULT 0,
   `orden` TINYINT UNSIGNED DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 4. Cabecera de Órdenes de Recepción y Trabajo (OT)
CREATE TABLE IF NOT EXISTS `sat_ordenes_trabajo` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `numero_orden` VARCHAR(50) NOT NULL UNIQUE,
   `tipo_servicio_id` INT UNSIGNED NOT NULL,
   `cliente_id` INT UNSIGNED NOT NULL,
   `tecnico_responsable_id` INT UNSIGNED DEFAULT NULL, -- Usuario / Técnico asignado
   `estado_id` INT UNSIGNED NOT NULL,
   `fecha_recepcion` DATETIME NOT NULL,
   `fecha_promesa_entrega` DATETIME DEFAULT NULL,
   `fecha_cierre_real` DATETIME DEFAULT NULL,
   `falla_reportada_cliente` TEXT NOT NULL,
   `diagnostico_tecnico` TEXT DEFAULT NULL,
   `trabajo_realizado` TEXT DEFAULT NULL,
   `accesorios_recibidos` TEXT DEFAULT NULL, -- Cargador, estuche, caucho de repuesto, etc.
   `detalles_visuales_danos` TEXT DEFAULT NULL, -- Rayones, golpes, abolladuras (JSON de coordenadas o texto)
   `total_mano_obra` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `total_repuestos` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `total_general` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `moneda_id` INT UNSIGNED NOT NULL DEFAULT 1,
   `tasa_cambio` DECIMAL(18, 6) NOT NULL DEFAULT 1.000000,
   `venta_id` BIGINT UNSIGNED DEFAULT NULL, -- Vinculación directa con la factura/nota generada
   `usuario_creador_id` INT UNSIGNED NOT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`tipo_servicio_id`) REFERENCES `sat_tipos_servicio`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`tecnico_responsable_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
   FOREIGN KEY (`estado_id`) REFERENCES `sat_estados_flujo`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE SET NULL,
   FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 5. Valores de Metadatos / Campos Dinámicos Guardados por Orden
CREATE TABLE IF NOT EXISTS `sat_ordenes_valores_campos` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `orden_id` BIGINT UNSIGNED NOT NULL,
   `campo_id` INT UNSIGNED NOT NULL,
   `valor` TEXT NOT NULL,
   FOREIGN KEY (`orden_id`) REFERENCES `sat_ordenes_trabajo`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`campo_id`) REFERENCES `sat_campos_personalizados`(`id`) ON DELETE CASCADE,
   UNIQUE KEY `uk_orden_campo` (`orden_id`, `campo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 6. Repuestos y Mano de Obra Consumidos en la Orden
CREATE TABLE IF NOT EXISTS `sat_ordenes_detalles` (
   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `orden_id` BIGINT UNSIGNED NOT NULL,

   `tipo_renglon` ENUM('MANO_OBRA_SERVICIO', 'REPUESTO_PRODUCTO') NOT NULL,
   `producto_id` INT UNSIGNED DEFAULT NULL, -- NULL si es mano de obra pura, o ID de productos si sale de inventario
   `deposito_id` INT UNSIGNED DEFAULT 1,
   `descripcion` VARCHAR(200) NOT NULL,
   `cantidad` DECIMAL(14, 4) NOT NULL DEFAULT 1.0000,
   `costo_unitario_historico` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `precio_unitario` DECIMAL(18, 4) NOT NULL,
   `subtotal` DECIMAL(18, 4) NOT NULL,
   `monto_iva` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
   `total` DECIMAL(18, 4) NOT NULL,
   `tecnico_comision_id` INT UNSIGNED DEFAULT NULL, -- Técnico que devenga la mano de obra
   FOREIGN KEY (`orden_id`) REFERENCES `sat_ordenes_trabajo`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`deposito_id`) REFERENCES `depositos`(`id`) ON DELETE RESTRICT,
   FOREIGN KEY (`tecnico_comision_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Semillas de Configuración Inicial
INSERT INTO `sat_tipos_servicio` (`id`, `codigo`, `nombre`, `prefijo_correlativo`) VALUES
(1, 'TALLER_AUTOMOTRIZ', 'Taller Mecánico / Automotriz', 'OT-MEC-'),
(2, 'SERVICIO_TECNICO', 'Servicio Técnico Electrónica / PC / Celulares', 'OT-TEC-')
ON DUPLICATE KEY UPDATE `codigo`=`codigo`;
INSERT INTO `sat_campos_personalizados` (`tipo_servicio_id`, `nombre_campo`, `slug`, `tipo_dato`, `es_obligatorio`, `orden`) VALUES
(1, 'Placa / Matrícula', 'placa', 'TEXTO', 1, 1),
(1, 'Marca / Modelo / Año', 'vehiculo_modelo', 'TEXTO', 1, 2),
(1, 'Kilometraje Actual', 'kilometraje', 'NUMERO', 1, 3),
(1, 'Nivel de Combustible', 'nivel_combustible', 'LISTA_SELECCION', 0, 4),
(2, 'Marca y Modelo', 'equipo_modelo', 'TEXTO', 1, 1),
(2, 'Serial / IMEI', 'serial_imei', 'TEXTO', 1, 2),
(2, 'Contraseña / Patrón de Desbloqueo', 'clave_desbloqueo', 'TEXTO', 0, 3)
ON DUPLICATE KEY UPDATE `slug`=`slug`;
UPDATE `sat_campos_personalizados` 
SET `opciones_lista` = '["Vacío", "1/4", "1/2", "3/4", "Lleno"]' 
WHERE `slug` = 'nivel_combustible';
INSERT INTO `sat_estados_flujo` (`id`, `nombre`, `slug`, `color_hex`, `es_estado_inicial`, `es_estado_final`, `permite_facturar`, `orden`) VALUES
(1, 'Recepción / Espera', 'RECIBIDO', '#64748b', 1, 0, 0, 1),
(2, 'En Diagnóstico', 'EN_DIAGNOSTICO', '#f59e0b', 0, 0, 0, 2),
(3, 'Presupuestado', 'PRESUPUESTADO', '#8b5cf6', 0, 0, 0, 3),
(4, 'Aprobado por Cliente', 'APROBADO', '#0284c7', 0, 0, 0, 4),
(5, 'En Reparación / Trabajo', 'EN_PROCESO', '#3b82f6', 0, 0, 0, 5),
(6, 'Listo para Entrega', 'LISTO', '#10b981', 0, 0, 1, 6),
(7, 'Facturado y Entregado', 'ENTREGADO', '#059669', 0, 1, 1, 7),
(8, 'Cancelado / No Aprobado', 'CANCELADO', '#ef4444', 0, 1, 0, 8)
ON DUPLICATE KEY UPDATE `slug`=`slug`;