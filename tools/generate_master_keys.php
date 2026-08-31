<?php
declare(strict_types=1);

/**
 * tools/generate_master_keys.php
 * Genera el par de claves RSA maestras para firma y validación de licencias
 */

$keysDir = dirname(__DIR__) . '/config/keys';
if (!is_dir($keysDir)) {
    mkdir($keysDir, 0755, true);
}

$toolsKeysDir = __DIR__ . '/master_keys';
if (!is_dir($toolsKeysDir)) {
    mkdir($toolsKeysDir, 0755, true);
}

// Buscar openssl.cnf en rutas conocidas o variable de entorno
$opensslCnfCandidates = [
    'C:/xampp/php/extras/ssl/openssl.cnf',
    'C:/xampp/php/extras/openssl/openssl.cnf',
    'C:/xampp/apache/bin/openssl.cnf',
    'C:/laragon/bin/php/openssl.cnf',
    getenv('OPENSSL_CONF') ?: '',
];

$cnfPath = null;
foreach ($opensslCnfCandidates as $candidate) {
    if (!empty($candidate) && file_exists($candidate)) {
        $cnfPath = $candidate;
        break;
    }
}

$config = [
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
];

if ($cnfPath) {
    $config["config"] = $cnfPath;
}

// Generar nuevo par de claves
$res = openssl_pkey_new($config);
if (!$res) {
    $errorMsg = openssl_error_string() ?: 'Error desconocido';
    echo "Error: No se pudo generar el par de claves RSA ({$errorMsg}).\n";
    exit(1);
}

// Extraer clave privada
$privKey = '';
if ($cnfPath) {
    openssl_pkey_export($res, $privKey, null, $config);
} else {
    openssl_pkey_export($res, $privKey);
}

// Extraer clave pública
$pubKeyDetails = openssl_pkey_get_details($res);
$pubKey = $pubKeyDetails["key"];

// Guardar en config/keys (usado por la aplicación y el instalador)
file_put_contents($keysDir . '/license_private_key.pem', $privKey);
file_put_contents($keysDir . '/license_public_key.pem', $pubKey);

// Guardar copia en tools/master_keys
file_put_contents($toolsKeysDir . '/license_private.key', $privKey);
file_put_contents($toolsKeysDir . '/license_public.key', $pubKey);

echo "============================================================\n";
echo "¡Par de Claves Maestras RSA generado con éxito!\n";
echo "Clave Privada: config/keys/license_private_key.pem\n";
echo "Clave Pública: config/keys/license_public_key.pem\n";
echo "============================================================\n";