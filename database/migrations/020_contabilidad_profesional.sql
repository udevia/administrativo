-- 1. Registro de Empresas / Clientes Contables administrados por el Despacho Contable
CREATE TABLE IF NOT EXISTS `contador_clientes_empresas` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `codigo_empresa` VARCHAR(20) NOT NULL UNIQUE,
   `razon_social` VARCHAR(150) NOT NULL,
   `rif` VARCHAR(25) NOT NULL UNIQUE,
   `ejercicio_fiscal_inicio` DATE NOT NULL,
   `ejercicio_fiscal_fin` DATE NOT NULL,
   `moneda_funcional` ENUM('USD', 'VES') DEFAULT 'USD',
   `estado` ENUM('ACTIVO', 'SUSPENDIDO', 'CERRADO') DEFAULT 'ACTIVO',
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contador_clientes_empresas` (`id`, `codigo_empresa`, `razon_social`, `rif`, `ejercicio_fiscal_inicio`, `ejercicio_fiscal_fin`)
VALUES (1, 'EMP-01', 'Empresa Principal', 'J-00000000-0', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))
ON DUPLICATE KEY UPDATE `codigo_empresa` = `codigo_empresa`;

-- 2. Periodos Contables y Cierres Mensuales/Anuales
CREATE TABLE IF NOT EXISTS `contabilidad_periodos` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `empresa_id` INT UNSIGNED NOT NULL,
   `ano` SMALLINT UNSIGNED NOT NULL,
   `mes` TINYINT UNSIGNED NOT NULL,
   `estado` ENUM('ABIERTO', 'CERRADO_AUDITADO') DEFAULT 'ABIERTO',
   `cerrado_por_usuario_id` INT UNSIGNED DEFAULT NULL,
   `fecha_cierre` TIMESTAMP NULL DEFAULT NULL,
   UNIQUE KEY `uk_empresa_periodo` (`empresa_id`, `ano`, `mes`),
   FOREIGN KEY (`empresa_id`) REFERENCES `contador_clientes_empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Adaptar Plan de Cuentas y Comprobantes para ser Multiempresa
ALTER TABLE `contabilidad_plan_cuentas`
ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
ADD CONSTRAINT `fk_puc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `contador_clientes_empresas`(`id`) ON DELETE CASCADE;

ALTER TABLE `contabilidad_comprobantes`
ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
ADD COLUMN `es_modificado_por_contador` TINYINT(1) DEFAULT 0 AFTER `estado`,
ADD COLUMN `contador_usuario_id` INT UNSIGNED DEFAULT NULL AFTER `usuario_id`,
ADD COLUMN `auditoria_modificacion` TEXT DEFAULT NULL AFTER `contador_usuario_id`,
ADD CONSTRAINT `fk_comp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `contador_clientes_empresas`(`id`) ON DELETE CASCADE;