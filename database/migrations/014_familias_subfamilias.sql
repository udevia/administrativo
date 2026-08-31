-- 1. Tabla de Familias (o Categorías Principales)
CREATE TABLE IF NOT EXISTS `familias` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `departamento_id` INT UNSIGNED NOT NULL,
   `codigo` VARCHAR(20) NOT NULL UNIQUE,
   `descripcion` VARCHAR(100) NOT NULL,
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Tabla de Sub-Familias
CREATE TABLE IF NOT EXISTS `subfamilias` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `familia_id` INT UNSIGNED NOT NULL,
   `codigo` VARCHAR(20) NOT NULL UNIQUE,
   `descripcion` VARCHAR(100) NOT NULL,
   `estado` TINYINT(1) DEFAULT 1,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`familia_id`) REFERENCES `familias`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 3. Vincular el producto directamente a la Sub-Familia
-- (Al vincularse a la Sub-Familia, hereda automáticamente la Familia y el Departamento)
ALTER TABLE `productos`
ADD COLUMN `subfamilia_id` INT UNSIGNED DEFAULT NULL AFTER `categoria_id`,
ADD CONSTRAINT `fk_productos_subfamilia` FOREIGN KEY (`subfamilia_id`) REFERENCES `subfamilias`(`id`) ON DELETE RESTRICT