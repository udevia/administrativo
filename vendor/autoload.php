<?php
/**
 * Autoloader PSR-4 nativo del proyecto mi ERP.
 *
 * Mapea el prefijo de namespace `App\` al directorio físico `app/`.
 * No requiere Composer: cualquier clase, controlador, modelo o servicio
 * agregado bajo app/ se detecta y carga dinámicamente.
 *
 * Ejemplo: App\Controllers\PosController -> app/Controllers/PosController.php
 */

declare(strict_types=1);

spl_autoload_register(static function (string $className): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $className, $len) !== 0) {
        return; // No es una clase del proyecto
    }

    $relativeClass = substr($className, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
