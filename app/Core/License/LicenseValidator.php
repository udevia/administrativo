<?php
declare(strict_types=1);

namespace App\Core\License;

use Exception;

class LicenseValidator {
    private string $publicKeyPath;

    public function __construct(string $publicKeyPath) {
        $this->publicKeyPath = $publicKeyPath;
    }

    public function validate(string $licenseFilePath): array {
        if (!file_exists($licenseFilePath)) {
            throw new Exception("Licencia no encontrada. Por favor active el sistema con su archivo .lic.");
        }

        $content = (string)file_get_contents($licenseFilePath);
        if (empty(trim($content))) {
            throw new Exception("Archivo de licencia corrupto o vacío.");
        }

        if (!file_exists($this->publicKeyPath)) {
            throw new Exception("Llave pública de validación no encontrada en el sistema.");
        }

        $publicKey = openssl_pkey_get_public((string)file_get_contents($this->publicKeyPath));
        if (!$publicKey) {
            throw new Exception("Error al cargar la llave pública de validación.");
        }

        // Intento 1: Paquete JSON V2 estructurado {"header": "MILIC_V2", "payload": "...", "signature": "..."}
        $package = json_decode($content, true);
        if (is_array($package) && isset($package['payload'], $package['signature'])) {
            $payloadRaw = base64_decode($package['payload']);
            $signatureRaw = base64_decode($package['signature']);

            $isValid = openssl_verify($payloadRaw, $signatureRaw, $publicKey, OPENSSL_ALGO_SHA256);
            if ($isValid !== 1) {
                throw new Exception("La firma digital de la licencia es inválida o el archivo fue alterado.");
            }

            $license = json_decode($payloadRaw, true);
            if (!is_array($license)) {
                throw new Exception("Estructura de datos de la licencia inválida.");
            }

            if (!empty($license['fecha_expiracion']) && strtotime((string)$license['fecha_expiracion']) < time()) {
                throw new Exception("La licencia expiró el: " . $license['fecha_expiracion']);
            }

            return $license;
        }

        // Intento 2: Formato binario A2LIC (compatibilidad legado)
        if (str_starts_with($content, "A2LIC") && strlen($content) >= 14) {
            $payloadLen = unpack("N", substr($content, 5, 4))[1];
            $payloadJson = substr($content, 9, $payloadLen);
            $sigOffset = 9 + $payloadLen;
            $sigLen = unpack("N", substr($content, $sigOffset, 4))[1];
            $signature = substr($content, $sigOffset + 4, $sigLen);

            $isValid = openssl_verify($payloadJson, $signature, $publicKey, OPENSSL_ALGO_SHA256);
            if ($isValid === 1) {
                $license = json_decode($payloadJson, true);
                if (is_array($license)) {
                    if (!empty($license['fecha_expiracion']) && strtotime((string)$license['fecha_expiracion']) < time()) {
                        throw new Exception("La licencia expiró el: " . $license['fecha_expiracion']);
                    }
                    return $license;
                }
            }
        }

        throw new Exception("Formato de licencia no reconocido o adulterado.");
    }
}
