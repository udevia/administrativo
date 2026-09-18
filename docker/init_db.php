<?php
declare(strict_types=1);

/**
 * Provisionamiento automático del contenedor (idempotente):
 *
 *  1. Si NO existe config/database.php  -> crea la base de datos,
 *     ejecuta las migraciones (001-032) y escribe la configuración
 *     a partir de las variables de entorno DB_*.
 *
 *  2. Si NO existe storage/installed.lock -> crea el usuario
 *     administrador (ADMIN_USER / ADMIN_PASSWORD) y sella la
 *     instalación, de modo que el sistema arranque listo para
 *     operar sin pasar por el Setup Wizard.
 *
 * En arranques posteriores no hace nada (los volúmenes persisten
 * config/ y storage/).
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\InstallerService;

$appDir     = dirname(__DIR__);
$configFile = $appDir . '/config/database.php';
$lockFile   = $appDir . '/storage/installed.lock';

if (!file_exists($configFile)) {
    echo "[init] Provisionando base de datos (host: " . (getenv('DB_HOST') ?: 'db') . ")...\n";
    $res = InstallerService::instalarBaseDatos([
        'host' => getenv('DB_HOST') ?: 'db',
        'port' => (int)(getenv('DB_PORT') ?: 3306),
        'user' => getenv('DB_USERNAME') ?: 'root',
        'pass' => getenv('DB_PASSWORD') ?: '',
        'name' => getenv('DB_DATABASE') ?: 'mi_admin_system',
    ]);
    echo "[init] " . $res['mensaje'] . "\n";
} else {
    echo "[init] Configuración de BD existente, sin reprovisionar.\n";
}

if (!file_exists($lockFile)) {
    echo "[init] Creando usuario administrador y sello de instalación...\n";
    require_once $configFile;
    $res = InstallerService::finalizarInstalacion([
        'usuario'  => getenv('ADMIN_USER') ?: 'admin',
        'password' => getenv('ADMIN_PASSWORD') ?: 'admin123',
        'nombre'   => getenv('ADMIN_NAME') ?: 'Administrador General',
    ]);
    echo "[init] " . $res['mensaje'] . "\n";
    echo "[init] Credenciales por defecto: " . (getenv('ADMIN_USER') ?: 'admin')
         . " / " . (getenv('ADMIN_PASSWORD') ?: 'admin123')
         . "  (cámbielas en la primera sesión)\n";
} else {
    echo "[init] Instalación previa detectada, sin cambios.\n";
}

echo "[init] Listo.\n";
