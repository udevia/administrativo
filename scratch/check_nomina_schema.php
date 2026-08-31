<?php
require 'vendor/autoload.php';
$db = \App\Core\Database::getConnection();
$stmt = $db->query("DESCRIBE nomina_empleados");
foreach ($stmt->fetchAll() as $row) {
    echo " - {$row['Field']} ({$row['Type']})\n";
}
