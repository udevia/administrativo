<?php
require 'vendor/autoload.php';
$db = \App\Core\Database::getConnection();
$stmt = $db->query("SHOW TABLES");
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
    echo $table . "\n";
}
