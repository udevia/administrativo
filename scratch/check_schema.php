<?php
require 'vendor/autoload.php';
$db = \App\Core\Database::getConnection();
$tables = ['departamentos', 'familias', 'categorias', 'depositos'];
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
