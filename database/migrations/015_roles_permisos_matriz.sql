-- 1. Tabla de Roles Maestros
CREATE TABLE IF NOT EXISTS `roles` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `nombre` VARCHAR(50) NOT NULL UNIQUE,
   `descripcion` VARCHAR(150) NOT NULL,
   `es_admin` TINYINT(1) DEFAULT 0,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Catálogo Maestro de Permisos Atómicos
CREATE TABLE IF NOT EXISTS `permisos` (
   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   `modulo` VARCHAR(50) NOT NULL,
   `slug` VARCHAR(80) NOT NULL UNIQUE,
   `descripcion` VARCHAR(150) NOT NULL,
   INDEX `idx_modulo` (`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 3. Tabla Intermedia: Permisos asignados a cada Rol
CREATE TABLE IF NOT EXISTS `roles_permisos` (
   `rol_id` INT UNSIGNED NOT NULL,
   `permiso_id` INT UNSIGNED NOT NULL,
   `valor_limite` VARCHAR(50) DEFAULT NULL, -- E.g. "15" para 15% máximo de descuento
   PRIMARY KEY (`rol_id`, `permiso_id`),
   FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`permiso_id`) REFERENCES `permisos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 4. Asignación de Roles a Usuarios
ALTER TABLE `usuarios`
ADD COLUMN `rol_id` INT UNSIGNED DEFAULT 1 AFTER `rol`,
ADD COLUMN `porcentaje_descuento_max` DECIMAL(5, 2) DEFAULT 0.00 AFTER `rol_id`,
ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT;

-- Semillas de Roles Iniciales
INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `es_admin`) VALUES
(1, 'Administrador General', 'Acceso total sin restricciones', 1),
(2, 'Supervisor de Caja / Tienda', 'Autoriza descuentos, anulaciones y cierres Z', 0),
(3, 'Cajero / Facturador POS', 'Emisión de documentos y cobros en mostrador', 0),
(4, 'Almacenista / Recepción', 'Entradas, salidas y traslados de inventario', 0),
(5, 'Vendedor de Preventa', 'Toma de pedidos móviles en ruta', 0)
ON DUPLICATE KEY UPDATE `nombre`=`nombre`;
-- Semillas de Permisos Base
INSERT INTO `permisos` (`modulo`, `slug`, `descripcion`) VALUES
('VENTAS', 'ventas.crear', 'Emitir facturas y documentos de venta'),
('VENTAS', 'ventas.modificar_precio', 'Modificar precio de venta en caliente'),
('VENTAS', 'ventas.aplicar_descuento', 'Aplicar porcentaje de descuento en factura'),
('VENTAS', 'ventas.anular', 'Anular ventas y procesar devoluciones'),
('VENTAS', 'ventas.cambiar_deposito', 'Cambiar el depósito origen al facturar'),
('INVENTARIO', 'inventario.ver_costos', 'Ver costos de compra y margen de ganancia'),
('INVENTARIO', 'inventario.ajustes', 'Procesar ajustes positivos y negativos de stock'),
('INVENTARIO', 'inventario.traslados', 'Realizar traslados entre depósitos'),
('COMPRAS', 'compras.crear_orden', 'Emitir órdenes de compra'),
('COMPRAS', 'compras.recibir_mercancia', 'Procesar recepciones en almacén virtual'),
('COMPRAS', 'compras.facturar', 'Asentar compras definitivas y generar CxP'),
('TESORERIA', 'caja.reporte_x', 'Emitir lectura fiscal Reporte X'),
('TESORERIA', 'caja.cierre_z', 'Emitir cierre fiscal definitivo Reporte Z'),
('CONFIGURACION', 'sistema.config_fiscal', 'Configurar parámetros fiscales e imprenta')
ON DUPLICATE KEY UPDATE `slug`=`slug`;