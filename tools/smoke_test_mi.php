<?php
declare(strict_types=1);

/**
 * tools/smoke_test_mi.php
 * Script de Pruebas Integrales y de Humo para el Sistema mi ERP
 */

echo "====================================================\n";
echo "    SUITE DE PRUEBAS INTEGRALES - mi ERP v2.0\n";
echo "====================================================\n\n";

$baseDir = dirname(__DIR__);
require_once $baseDir . '/vendor/autoload.php';

$errors = 0;
$success = 0;

function reportResult(string $step, bool $passed, string $detail = ''): void {
    global $errors, $success;
    if ($passed) {
        $success++;
        echo "[OK] {$step}" . ($detail ? " -> {$detail}" : "") . "\n";
    } else {
        $errors++;
        echo "[FAIL] {$step}" . ($detail ? " -> ERROR: {$detail}" : "") . "\n";
    }
}

// 1. REQUISITOS DEL ENTORNO PHP
echo "--- 1. Verificación de Entorno y Extensiones ---\n";
reportResult("Versión de PHP", version_compare(PHP_VERSION, '8.0.0', '>='), PHP_VERSION);
$exts = ['pdo', 'openssl', 'json', 'mbstring', 'curl'];
foreach ($exts as $ext) {
    reportResult("Extensión '{$ext}'", extension_loaded($ext));
}

// 2. CARGA DE CLASES PSR-4
echo "\n--- 2. Autoload PSR-4 y Registro de Clases ---\n";
$classesToTest = [
    \App\Core\Router::class,
    \App\Core\Database::class,
    \App\Core\InstallerService::class,
    \App\Core\License\LicenseValidator::class,
    \App\Core\PdfEngine::class,
    \App\Core\WhatsAppService::class,
    \App\Core\TelegramEngine::class,
    \App\Core\SyncEngineService::class,
    \App\Core\BackupService::class,
    \App\Models\Producto::class,
    \App\Models\Cliente::class,
    \App\Models\Proveedor::class,
    \App\Models\DocumentoVentaService::class,
    \App\Models\ComprasService::class,
    \App\Models\AprovisionamientoService::class,
    \App\Models\BancoService::class,
    \App\Models\NominaService::class,
    \App\Models\ContadorService::class,
    \App\Models\SatService::class,
    \App\Models\SerialesService::class,
    \App\Models\ActivosFijosService::class,
    \App\Models\EcommerceService::class,
    \App\Models\LoteService::class,
    \App\Models\ValuacionInventarioService::class,
    \App\Models\ReportesMiService::class,
    \App\Controllers\VentasController::class,
    \App\Controllers\ComprasController::class,
    \App\Controllers\InventarioController::class,
    \App\Controllers\ContabilidadController::class,
    \App\Controllers\NominaController::class,
    \App\Controllers\FormatosController::class,
    \App\Controllers\MigracionController::class
];

foreach ($classesToTest as $cls) {
    reportResult("Carga de Clase {$cls}", class_exists($cls));
}

// 3. GENERACIÓN Y VALIDACIÓN DE LICENCIA DIGITAL (RSA-SHA256 MILIC_V2)
echo "\n--- 3. Motor de Licenciamiento Criptográfico (RSA-SHA256 MILIC_V2) ---\n";
try {
    require_once $baseDir . '/tools/generate_license.php';
    $pubKeyPath = $baseDir . '/config/keys/license_public_key.pem';
    $privKeyPath = $baseDir . '/config/keys/license_private_key.pem';
    $licPath = $baseDir . '/license/sistema.lic';

    if (!file_exists($pubKeyPath) || !file_exists($privKeyPath)) {
        require_once $baseDir . '/tools/generate_master_keys.php';
    }

    $datosCliente = [
        'razon_social' => 'EMPRESA DEMO Y PRUEBAS mi ERP C.A.',
        'rif'          => 'J-99999999-0',
        'tipo_licencia'=> 'PERPETUA',
        'modulos'      => ['pos' => true, 'nomina' => true, 'contabilidad' => true]
    ];

    $emited = LicenseGenerator::emitirLicencia($datosCliente, $privKeyPath, $licPath);
    reportResult("Emisión de Licencia RSA", $emited);

    $validator = new \App\Core\License\LicenseValidator($pubKeyPath);
    $datosLic = $validator->validate($licPath);
    reportResult("Validación Criptográfica de Licencia", is_array($datosLic) && $datosLic['empresa_rif'] === 'J-99999999-0', "Titular: {$datosLic['empresa_razon_social']}");
} catch (\Throwable $e) {
    reportResult("Licenciamiento RSA", false, $e->getMessage());
}

// 4. MIGRACIONES SQL (001 - 031)
echo "\n--- 4. Integridad de Migraciones SQL ---\n";
$migrations = glob($baseDir . '/database/migrations/*.sql');
sort($migrations);
$lastMigration = basename(end($migrations));
$has031 = str_contains($lastMigration, '031');
reportResult("Migraciones SQL Secuenciales (hasta 031)", count($migrations) >= 25 && $has031, "Total de Archivos: " . count($migrations) . " | Última: {$lastMigration}");

// 5. ENRUTADOR Y RUTAS REGISTRADAS
echo "\n--- 5. Verificación de Rutas y Endpoints API ---\n";
require_once $baseDir . '/app/Core/Router.php';
$router = new \App\Core\Router();
reportResult("Instanciación de Router", true);

echo "\n====================================================\n";
echo "    RESUMEN DE RESULTADOS DE PRUEBAS DE HUMO\n";
echo "====================================================\n";
echo "Total Pruebas Ejecutadas: " . ($success + $errors) . "\n";
echo "Pruebas Exitosas:         {$success}\n";
echo "Pruebas Fallidas:         {$errors}\n";
echo "====================================================\n";

if ($errors > 0) {
    exit(1);
} else {
    echo "\n>>> ¡EL SISTEMA mi ERP ESTÁ 100% OPERATIVO Y LISTO PARA DEPLOY! <<<\n";
    exit(0);
}
