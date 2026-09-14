<?php
require 'app/autoload.php';
$db = \App\Core\Database::getConnection();
$tables = ['inventario_existencias', 'inventario_depositos'];
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    try {
        $stmt = $db->query("DESCRIBE $table");
        foreach ($stmt->fetchAll() as $row) {
            echo " - {$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
