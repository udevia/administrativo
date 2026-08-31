-- ====================================================================
-- Migración 031: Tablas de Sincronización, Lotes, Capas FIFO y Vistas de Compatibilidad mi ERP
-- ====================================================================

-- 1. Cola de Eventos de Sincronización Nube / Local
CREATE TABLE IF NOT EXISTS `sync_queue` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tabla_afectada` VARCHAR(60) NOT NULL,
    `operacion` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    `registro_id` INT UNSIGNED NOT NULL,
    `payload_json` LONGTEXT NULL,
    `estado` ENUM('PENDIENTE', 'SINCRONIZADO', 'ERROR') DEFAULT 'PENDIENTE',
    `intentos` INT UNSIGNED DEFAULT 0,
    `ultimo_error` TEXT NULL,
    `sincronizado_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sync_estado` (`estado`),
    INDEX `idx_sync_tabla` (`tabla_afectada`, `registro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Configuración del Motor de Sincronización
CREATE TABLE IF NOT EXISTS `configuracion_sync` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `servidor_nube_url` VARCHAR(255) NULL,
    `api_token` VARCHAR(255) NULL,
    `intervalo_segundos` INT UNSIGNED DEFAULT 60,
    `ultimo_ping_exitoso` DATETIME NULL,
    `estado` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Trazabilidad de Lotes y Vencimientos de Productos
CREATE TABLE IF NOT EXISTS `producto_lotes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT UNSIGNED NOT NULL,
    `deposito_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `numero_lote` VARCHAR(50) NOT NULL,
    `fecha_fabricacion` DATE NULL,
    `fecha_vencimiento` DATE NOT NULL,
    `existencia` DECIMAL(14,4) DEFAULT 0.0000,
    `costo_lote` DECIMAL(14,4) DEFAULT 0.0000,
    `estado` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_prod_dep_lote` (`producto_id`, `deposito_id`, `numero_lote`),
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`deposito_id`) REFERENCES `depositos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Capas para Valuación de Inventario FIFO / PEPS
CREATE TABLE IF NOT EXISTS `inventario_capas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT UNSIGNED NOT NULL,
    `deposito_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `compra_id` INT UNSIGNED NULL,
    `cantidad_inicial` DECIMAL(14,4) NOT NULL,
    `cantidad_restante` DECIMAL(14,4) NOT NULL,
    `costo_unitario_usd` DECIMAL(14,4) NOT NULL,
    `fecha_ingreso` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `estado` ENUM('ABIERTA', 'AGOTADA') DEFAULT 'ABIERTA',
    INDEX `idx_capas_prod_estado` (`producto_id`, `estado`, `fecha_ingreso`),
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Histórico de Respaldos del Sistema
CREATE TABLE IF NOT EXISTS `respaldos_sistema` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre_archivo` VARCHAR(255) NOT NULL,
    `tamano_bytes` BIGINT UNSIGNED NOT NULL,
    `total_tablas` INT UNSIGNED NOT NULL,
    `tipo` VARCHAR(30) DEFAULT 'COMPLETO',
    `checksum_sha256` VARCHAR(64) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Vista de compatibilidad: inventario_existencias -> producto_deposito
CREATE OR REPLACE VIEW `inventario_existencias` AS 
SELECT 
    pd.id,
    pd.producto_id,
    pd.deposito_id,
    pd.existencia,
    pd.existencia_comprometida,
    pd.existencia_por_llegar,
    pd.punto_reorden,
    pd.ubicacion_pasillo AS ubicacion
FROM `producto_deposito` pd;

-- 7. Vista de compatibilidad: ordenes_compra -> compras
CREATE OR REPLACE VIEW `ordenes_compra` AS 
SELECT 
    c.id,
    c.numero_factura AS numero_orden,
    c.proveedor_id,
    c.deposito_id,
    c.usuario_id,
    c.fecha_emision,
    c.fecha_vencimiento,
    c.condicion_pago,
    c.subtotal_neto,
    c.monto_iva,
    c.total_general,
    c.estado,
    c.created_at
FROM `compras` c
WHERE c.tipo_documento = 'ORDEN_COMPRA';
