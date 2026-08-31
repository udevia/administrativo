<?php
namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;
    private static ?Database $selfInstance = null;
    
    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): self {
        if (self::$selfInstance === null) {
            self::$selfInstance = new self();
        }
        return self::$selfInstance;
    }

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1');
            $port = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306');
            $db   = defined('DB_DATABASE') ? DB_DATABASE : (getenv('DB_DATABASE') ?: 'mi_admin_system');
            $user = defined('DB_USERNAME') ? DB_USERNAME : (getenv('DB_USERNAME') ?: 'root');
            $pass = defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') ?: '');
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function beginTransaction(): bool {
        if (self::getConnection()->inTransaction()) {
            return true;
        }
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->commit();
        }
        return true;
    }

    public static function rollBack(): bool {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return true;
    }

    public static function getTasaActualUsd(): float {
        try {
            $db = self::getConnection();
            $stmt = $db->query("SELECT tasa FROM tasas_cambio WHERE moneda_id = 2 ORDER BY fecha DESC, id DESC LIMIT 1");
            $val = $stmt->fetchColumn();
            return $val ? (float)$val : 1.0;
        } catch (\Throwable $e) {
            return 1.0;
        }
    }
}
