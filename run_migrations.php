<?php
require 'vendor/autoload.php';
$db = \App\Core\Database::getConnection();
$dir = __DIR__ . '/database/migrations/';
$files = scandir($dir);
sort($files);
foreach ($files as $f) {
    if (strpos($f, '.sql') !== false) {
        echo "Executing: $f\n";
        $sql = file_get_contents($dir . $f);
        try {
            $db->exec($sql);
            echo " -> SUCCESS\n";
        } catch (\PDOException $e) {
            echo " -> ERROR: " . $e->getMessage() . "\n";
        }
    }
}
