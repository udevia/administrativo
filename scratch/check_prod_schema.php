<?php
require 'app/autoload.php';
$db = \App\Core\Database::getConnection();
$stmt = $db->query("DESCRIBE productos");
foreach ($stmt->fetchAll() as $row) {
    echo " - {$row['Field']} ({$row['Type']})\n";
}
