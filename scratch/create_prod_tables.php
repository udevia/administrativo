<?php
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$db = Database::getConnection();
$db->exec("
    CREATE TABLE IF NOT EXISTS produccion_formulas (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        nombre_formula VARCHAR(255), 
        producto_terminado_id INT, 
        rendimiento_lote DECIMAL(10,2) DEFAULT 1, 
        costo_estimado DECIMAL(10,2) DEFAULT 0, 
        activa TINYINT(1) DEFAULT 1, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ); 
    CREATE TABLE IF NOT EXISTS produccion_formulas_detalles (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        formula_id INT, 
        material_id INT, 
        cantidad_requerida DECIMAL(10,4), 
        unidad VARCHAR(20), 
        costo_estimado DECIMAL(10,2), 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ); 
    CREATE TABLE IF NOT EXISTS produccion_ordenes (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        numero_orden VARCHAR(50), 
        formula_id INT, 
        cantidad_planificada DECIMAL(10,2), 
        cantidad_fabricada_real DECIMAL(10,2) DEFAULT 0, 
        costo_total_materia_prima DECIMAL(10,2) DEFAULT 0, 
        costo_unitario_terminado DECIMAL(10,2) DEFAULT 0, 
        estado ENUM('PLANIFICADA', 'EN_PROCESO', 'COMPLETADA', 'CANCELADA') DEFAULT 'PLANIFICADA', 
        deposito_id INT, 
        usuario_id INT, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ); 
    CREATE TABLE IF NOT EXISTS produccion_ordenes_consumo (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        orden_id INT, 
        material_id INT, 
        cantidad_consumida DECIMAL(10,4), 
        costo_unitario DECIMAL(10,2), 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");
echo "Tablas creadas";
