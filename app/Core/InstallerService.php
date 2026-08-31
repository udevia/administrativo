<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Exception;
use App\Core\License\LicenseValidator;

class InstallerService {
    private static function getRootPath(): string {
        return defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
    }

    private static function getLockFile(): string {
        return defined('LOCK_FILE') ? LOCK_FILE : self::getRootPath() . '/storage/installed.lock';
    }

    /**
     * Paso 1: Valida requerimientos del servidor y extensiones
     */
    public static function checkRequisitos(): array {
        $root = self::getRootPath();
        $phpVersionOk = version_compare(PHP_VERSION, '8.0.0', '>=');
        
        $extensiones = [
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'openssl'   => extension_loaded('openssl'),
            'zlib'      => extension_loaded('zlib'),
            'gd'        => extension_loaded('gd'),
            'mbstring'  => extension_loaded('mbstring'),
            'curl'      => extension_loaded('curl'),
            'json'      => extension_loaded('json')
        ];

        $directorios = [
            'storage'           => is_writable($root . '/storage') || @mkdir($root . '/storage', 0755, true),
            'storage/backups'   => is_writable($root . '/storage/backups') || @mkdir($root . '/storage/backups', 0755, true),
            'storage/lic'       => is_writable($root . '/storage/lic') || @mkdir($root . '/storage/lic', 0755, true),
            'config'            => is_writable($root . '/config') || @mkdir($root . '/config', 0755, true)
        ];

        $criticas = ['pdo_mysql', 'openssl', 'mbstring', 'json', 'curl'];
        $criticasOk = true;
        foreach ($criticas as $ext) {
            if (empty($extensiones[$ext])) {
                $criticasOk = false;
                break;
            }
        }

        $todoOk = $phpVersionOk 
            && $criticasOk
            && !in_array(false, $directorios, true);

        return [
            'todo_ok'      => $todoOk,
            'php_version'  => ['actual' => PHP_VERSION, 'requerida' => '8.0.0+', 'valido' => $phpVersionOk],
            'extensiones'  => $extensiones,
            'directorios'  => $directorios
        ];
    }

    /**
     * Paso 2: Valida credenciales, crea base de datos y corre las migraciones SQL
     */
    public static function instalarBaseDatos(array $dbConfig): array {
        $root = self::getRootPath();
        try {
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = (int)($dbConfig['port'] ?? 3306);
            $user = $dbConfig['user'] ?? 'root';
            $pass = $dbConfig['pass'] ?? '';
            $name = $dbConfig['name'] ?? 'sistema_admin';

            // 1. Conexión al servidor sin especificar base de datos
            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
            ]);

            // 2. Crear base de datos InnoDB UTF8MB4
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$name}`;");

            // 3. Ejecutar archivos de migración secuenciales
            $migrationsDir = $root . '/database/migrations/';
            $sqlFiles = glob($migrationsDir . '*.sql') ?: [];
            sort($sqlFiles);

            $migracionesEjecutadas = 0;
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            foreach ($sqlFiles as $file) {
                $sql = file_get_contents($file);
                if (!empty(trim((string)$sql))) {
                    try {
                        $pdo->exec((string)$sql);
                    } catch (Exception $eFile) {
                        // Fallback: separar por punto y coma y ejecutar individualmente
                        $statements = array_filter(array_map('trim', explode(";\n", (string)$sql)));
                        foreach ($statements as $stmtSql) {
                            if (!empty($stmtSql)) {
                                try {
                                    $pdo->exec($stmtSql . ";");
                                } catch (Exception $eSingle) {
                                    // Ignorar errores de objetos/columnas ya existentes en re-ejecución
                                }
                            }
                        }
                    }
                    $migracionesEjecutadas++;
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

            // 4. Escribir archivo de configuración config/database.php
            $configDir = $root . '/config';
            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }

            $configContent = "<?php\n"
                . "// Generado automáticamente por el Setup Wizard\n"
                . "define('DB_HOST', " . var_export($host, true) . ");\n"
                . "define('DB_PORT', " . var_export((string)$port, true) . ");\n"
                . "define('DB_USERNAME', " . var_export($user, true) . ");\n"
                . "define('DB_PASSWORD', " . var_export($pass, true) . ");\n"
                . "define('DB_DATABASE', " . var_export($name, true) . ");\n";

            file_put_contents($configDir . '/database.php', $configContent);

            return [
                'status'     => 'success',
                'mensaje'    => "Base de datos '{$name}' aprovisionada con éxito. {$migracionesEjecutadas} migraciones aplicadas."
            ];
        } catch (Exception $e) {
            throw new Exception("Fallo en la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Paso 3: Valida criptográficamente el archivo .lic y lo ubica en storage/lic/license.lic
     */
    public static function procesarLicencia(array $fileLic): array {
        $root = self::getRootPath();
        if (!isset($fileLic['tmp_name']) || !file_exists($fileLic['tmp_name'])) {
            throw new Exception("Debe cargar un archivo de licencia válido (.lic).");
        }

        $tmpPath = $fileLic['tmp_name'];
        $pubKeyPath = $root . '/config/keys/license_public_key.pem';
        if (!file_exists($pubKeyPath)) {
            $pubKeyPath = $root . '/config/keys/license_public.key';
        }

        if (!file_exists($pubKeyPath)) {
            throw new Exception("Llave pública de validación de licencias no encontrada en el sistema.");
        }

        $validator = new LicenseValidator($pubKeyPath);
        $licenseData = $validator->validate($tmpPath);

        // Guardar archivo validado
        $destino = $root . '/license/sistema.lic';
        $destinoDir = dirname($destino);
        if (!is_dir($destinoDir)) {
            mkdir($destinoDir, 0755, true);
        }
        copy($tmpPath, $destino);

        // Guardar copia en storage/lic/
        $storageLic = $root . '/storage/lic/license.lic';
        @copy($tmpPath, $storageLic);

        // Guardar datos en la tabla empresa si existe la conexión
        $configFile = $root . '/config/database.php';
        if (file_exists($configFile)) {
            require_once $configFile;
            $dbHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
            $dbPort = defined('DB_PORT') ? DB_PORT : 3306;
            $dbUser = defined('DB_USERNAME') ? DB_USERNAME : (defined('DB_USER') ? DB_USER : 'root');
            $dbPass = defined('DB_PASSWORD') ? DB_PASSWORD : (defined('DB_PASS') ? DB_PASS : '');
            $dbName = defined('DB_DATABASE') ? DB_DATABASE : (defined('DB_NAME') ? DB_NAME : 'sistema_admin');

            try {
                $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
                $stmt = $pdo->prepare("
                    INSERT INTO empresa (id, razon_social, rif, direccion_fiscal)
                    VALUES (1, :nom, :rif, :dir)
                    ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social), rif = VALUES(rif), direccion_fiscal = VALUES(direccion_fiscal)
                ");
                $stmt->execute([
                    'nom' => $licenseData['empresa_razon_social'],
                    'rif' => $licenseData['empresa_rif'],
                    'dir' => $licenseData['direccion_fiscal'] ?? 'Dirección Fiscal Principal'
                ]);
            } catch (\Throwable $e) {
                // Silencioso si la BD aún no está lista
            }
        }

        return [
            'status'          => 'success',
            'empresa'         => $licenseData['empresa_razon_social'],
            'rif'             => $licenseData['empresa_rif'],
            'tipo_licencia'   => $licenseData['tipo_licencia'] ?? 'ILIMITADA',
            'expiracion'      => $licenseData['fecha_expiracion'] ?? 'PERMANENTE'
        ];
    }

    /**
     * Paso 4: Registra el usuario administrador inicial y finaliza la instalación
     */
    public static function finalizarInstalacion(array $adminData): array {
        $root = self::getRootPath();
        $configFile = $root . '/config/database.php';
        if (!file_exists($configFile)) {
            throw new Exception("Archivo de configuración de base de datos no encontrado.");
        }

        require_once $configFile;
        $dbHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $dbPort = defined('DB_PORT') ? DB_PORT : 3306;
        $dbUser = defined('DB_USERNAME') ? DB_USERNAME : (defined('DB_USER') ? DB_USER : 'root');
        $dbPass = defined('DB_PASSWORD') ? DB_PASSWORD : (defined('DB_PASS') ? DB_PASS : '');
        $dbName = defined('DB_DATABASE') ? DB_DATABASE : (defined('DB_NAME') ? DB_NAME : 'sistema_admin');

        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);

        $usuario = trim((string)($adminData['usuario'] ?? 'admin'));
        $nombre  = trim((string)($adminData['nombre'] ?? 'Administrador General'));
        $pass    = password_hash((string)($adminData['password'] ?? 'admin123'), PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (id, nombre, usuario, password, rol, rol_id, estado)
            VALUES (1, :nom, :usr, :pwd, 'admin', 1, 1)
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), password = VALUES(password)
        ");
        $stmt->execute([
            'nom' => $nombre,
            'usr' => $usuario,
            'pwd' => $pass
        ]);

        // Crear el archivo de bloqueo para sellar la instalación
        $lockData = json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ], JSON_PRETTY_PRINT);
        
        $lockFile = self::getLockFile();
        $lockDir = dirname($lockFile);
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        file_put_contents($lockFile, $lockData);

        return [
            'status'  => 'success',
            'mensaje' => 'Instalación completada exitosamente. El sistema está listo para operar.'
        ];
    }
}