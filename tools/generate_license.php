<?php
declare(strict_types=1);

/**
 * tools/generate_license.php
 * Generador de archivos de licencia (.lic) firmados digitalmente con RSA-SHA256
 */

class LicenseGenerator {
    /**
     * Emite un archivo de licencia inalterable con firma digital
     */
    public static function emitirLicencia(array $datosCliente, string $privateKeyPath, string $archivoDestino): bool {
        if (!file_exists($privateKeyPath)) {
            throw new Exception("Clave privada RSA no encontrada en: {$privateKeyPath}");
        }

        $privateKey = openssl_pkey_get_private((string)file_get_contents($privateKeyPath));
        if (!$privateKey) {
            throw new Exception("La clave privada RSA es inválida.");
        }

        // 1. Estructura de la Licencia
        $payloadArray = [
            'empresa_razon_social' => trim((string)$datosCliente['razon_social']),
            'empresa_rif'          => strtoupper(trim((string)$datosCliente['rif'])),
            'direccion_fiscal'     => $datosCliente['direccion_fiscal'] ?? 'Dirección Fiscal Principal',
            'tipo_licencia'        => $datosCliente['tipo_licencia'] ?? 'PERPETUA',
            'fecha_emision'        => date('Y-m-d H:i:s'),
            'fecha_expiracion'     => $datosCliente['fecha_expiracion'] ?? null,
            'limite_usuarios'      => (int)($datosCliente['limite_usuarios'] ?? 0), // 0 = Ilimitado
            'limite_estaciones'    => (int)($datosCliente['limite_estaciones'] ?? 0),
            'modulos_activos'      => [
                'pos_facturacion'    => (bool)($datosCliente['modulos']['pos'] ?? true),
                'preventa_movil'     => (bool)($datosCliente['modulos']['preventa'] ?? true),
                'contabilidad_pro'   => (bool)($datosCliente['modulos']['contabilidad'] ?? true),
                'nomina'             => (bool)($datosCliente['modulos']['nomina'] ?? true),
                'produccion_bom'     => (bool)($datosCliente['modulos']['produccion'] ?? true),
                'servicios_sat'      => (bool)($datosCliente['modulos']['sat'] ?? true),
                'activos_fijos'      => (bool)($datosCliente['modulos']['activos'] ?? true),
                'ecommerce_api'      => (bool)($datosCliente['modulos']['ecommerce'] ?? true),
                'bot_telegram'       => (bool)($datosCliente['modulos']['telegram'] ?? true),
                'whatsapp_notify'    => (bool)($datosCliente['modulos']['whatsapp'] ?? true)
            ],
            'nonce_seguridad'      => bin2hex(random_bytes(16))
        ];

        $payloadJson = json_encode($payloadArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false) {
            throw new Exception("Error al codificar payload de licencia a JSON.");
        }

        // 2. Firmar digitalmente con RSA-SHA256
        $signature = '';
        $signOk = openssl_sign($payloadJson, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$signOk) {
            throw new Exception("Error al firmar la licencia digitalmente.");
        }

        // 3. Crear paquete con Base64
        $package = [
            'header'    => 'MILIC_V2',
            'payload'   => base64_encode($payloadJson),
            'signature' => base64_encode($signature)
        ];

        $archivoContenido = json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $destinoDir = dirname($archivoDestino);
        if (!is_dir($destinoDir)) {
            mkdir($destinoDir, 0755, true);
        }

        file_put_contents($archivoDestino, $archivoContenido);
        return true;
    }
}

// Ejemplo de Ejecución CLI
if (php_sapi_name() === 'cli' && (!isset($argv[0]) || realpath($argv[0]) === realpath(__FILE__))) {
    $clienteEjemplo = [
        'razon_social'     => 'DISTRIBUIDORA CENTRAL CARABOBO C.A.',
        'rif'              => 'J-12345678-9',
        'direccion_fiscal' => 'Av. Bolívar Norte, Centro Comercial La Granja, Valencia, Edo. Carabobo',
        'tipo_licencia'    => 'PERPETUA',
        'fecha_expiracion' => null,
        'limite_usuarios'  => 0,
        'limite_estaciones'=> 0,
        'modulos'          => [
            'pos'          => true,
            'preventa'     => true,
            'contabilidad' => true,
            'nomina'       => true,
            'produccion'   => true,
            'sat'          => true,
            'activos'      => true,
            'ecommerce'    => true,
            'telegram'     => true,
            'whatsapp'     => true
        ]
    ];

    $privateKey = dirname(__DIR__) . '/config/keys/license_private_key.pem';
    if (!file_exists($privateKey)) {
        require_once __DIR__ . '/generate_master_keys.php';
    }

    $salida = dirname(__DIR__) . '/license/sistema.lic';
    LicenseGenerator::emitirLicencia($clienteEjemplo, $privateKey, $salida);
    echo "¡Archivo de licencia emitido con éxito en: {$salida}!\n";
}