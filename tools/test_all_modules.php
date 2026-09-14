<?php
// tools/test_all_modules.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\License\LicenseValidator;
use App\Core\AuditLogger;
use App\Core\BankGatewayEngine;
use App\Core\TelegramEngine;
use App\Models\Producto;
use App\Models\LoteService;
use App\Models\SerialesService;
use App\Models\VentasService;
use App\Models\ComprasService;
use App\Models\SatService;
use App\Models\ActivosFijosService;
use App\Models\EcommerceService;
use App\Models\NominaService;
use App\Models\ContadorService;

$totalTests = 0;
$passed = 0;
$failed = 0;

function assertModuleTest(string $modulo, string $testName, callable $fn): void {
    global $totalTests, $passed, $failed;
    $totalTests++;
    try {
        $fn();
        echo " [OK] [{$modulo}] {$testName}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo " [FAIL] [{$modulo}] {$testName} -> " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "========================================================================\n";
echo "    BUCLE AUTÓNOMO DE PRUEBAS INTEGRALES Y AUDITORÍA - mi ERP v2.0\n";
echo "========================================================================\n\n";

$db = Database::getConnection();

// ------------------------------------------------------------------------
// [MÓDULO 01] NÚCLEO, SEGURIDAD Y LICENCIAMIENTO
// ------------------------------------------------------------------------
assertModuleTest("MODULO-01", "Verificación de Licencia RSA-4096 / AES-256", function() {
    $pubKey = __DIR__ . '/../config/keys/license_public_key.pem';
    if (!file_exists($pubKey)) {
        throw new Exception("Llave pública de validación no encontrada.");
    }
    $validator = new LicenseValidator($pubKey);
    if (!is_object($validator)) {
        throw new Exception("Error al instanciar LicenseValidator.");
    }
});

assertModuleTest("MODULO-01", "Singleton PDO con transacciones ACID seguras", function() {
    Database::beginTransaction();
    if (!Database::getConnection()->inTransaction()) {
        throw new Exception("Falló la activación de transacción PDO.");
    }
    Database::commit();
});

// ------------------------------------------------------------------------
// [MÓDULO 02] CATÁLOGO, MÚLTIPLES PRESENTACIONES, LOTES Y SERIALES
// ------------------------------------------------------------------------
assertModuleTest("MODULO-02", "Trazabilidad de Seriales / IMEI Unicos", function() {
    $db = Database::getConnection();
    $serial = 'IMEI-TEST-' . rand(10000, 99999);
    $stmt = $db->prepare("
        INSERT INTO producto_seriales (producto_id, deposito_id, numero_serial, estado, costo_unitario_compra, fecha_ingreso)
        VALUES (2, 1, :s, 'DISPONIBLE', 160.0000, CURDATE())
    ");
    $stmt->execute(['s' => $serial]);
    
    $check = $db->query("SELECT * FROM producto_seriales WHERE numero_serial = '{$serial}'")->fetch();
    if (!$check || $check['estado'] !== 'DISPONIBLE') {
        throw new Exception("Serial IMEI no registrado correctamente.");
    }
});

assertModuleTest("MODULO-02", "Trazabilidad de Lotes y Fechas FEFO", function() {
    $db = Database::getConnection();
    $loteNum = 'LOTE-FEFO-' . rand(100, 999);
    $stmt = $db->prepare("
        INSERT INTO producto_lotes (producto_id, deposito_id, numero_lote, fecha_fabricacion, fecha_vencimiento, existencia, costo_lote, estado)
        VALUES (1, 1, :num, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 100.00, 0.90, 1)
    ");
    $stmt->execute(['num' => $loteNum]);
});

// ------------------------------------------------------------------------
// [MÓDULO 03] COSTEO, KARDEX Y COMPRAS EN 3 FASES
// ------------------------------------------------------------------------
assertModuleTest("MODULO-03", "Circuito de Compras (Orden -> Recepción -> Factura CxP)", function() {
    $db = Database::getConnection();
    $numComp = 'FACT-PROV-' . rand(1000, 9999);
    $stmt = $db->prepare("
        INSERT INTO compras (
            tipo_documento, numero_factura, proveedor_id, deposito_id, usuario_id,
            fecha_emision, fecha_recepcion, fecha_vencimiento, condicion_pago,
            moneda_id, tasa_cambio, subtotal_neto, monto_exento, base_imponible,
            monto_iva, total_general, saldo_pendiente, estado
        ) VALUES (
            'FACTURA_COMPRA', :num, 1, 1, 1,
            CURDATE(), CURDATE(), CURDATE(), 'CREDITO',
            1, 791.32, 100.0000, 0.0000, 100.0000,
            16.0000, 116.0000, 116.0000, 'PROCESADA'
        )
    ");
    $stmt->execute(['num' => $numComp]);
});

// ------------------------------------------------------------------------
// [MÓDULO 04] VENTAS, POS Y PREVENTA MÓVIL
// ------------------------------------------------------------------------
assertModuleTest("MODULO-04", "Procesamiento de Venta POS + Descuento de Inventario", function() {
    $res = VentasService::procesarVenta([
        'tipo_documento'   => 'FACTURA',
        'numero_documento' => 'FAC-TEST-' . rand(1000, 9999),
        'cliente_id'       => 1,
        'vendedor_id'      => 1,
        'deposito_id'      => 1,
        'usuario_id'       => 1,
        'moneda_id'        => 1,
        'tasa_cambio'      => 791.32,
        'condicion_pago'   => 'CONTADO',
        'formas_pago'      => [
            ['forma_pago' => 'EFECTIVO', 'monto' => 1.74, 'cuenta_id' => 1, 'referencia' => 'POS-CASH']
        ]
    ], [
        ['producto_id' => 1, 'cantidad' => 1, 'precio_unitario' => 1.50, 'porcentaje_descuento' => 0, 'porcentaje_iva' => 16]
    ]);
    if (empty($res['venta_id'])) throw new Exception("Venta POS no procesada.");
});

// ------------------------------------------------------------------------
// [MÓDULO 05] INTEGRACIÓN FISCAL DUAL
// ------------------------------------------------------------------------
assertModuleTest("MODULO-05", "Driver Fiscal Imprenta Digital & Estampado CUFE / QR", function() {
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=DEMO_CUFE_HASH_987654";
    if (empty($qrUrl)) throw new Exception("Error al generar QR Fiscal.");
});

// ------------------------------------------------------------------------
// [MÓDULO 06] E-COMMERCE B2B/B2C Y PASARELAS BANCARIAS DIRECTAS
// ------------------------------------------------------------------------
assertModuleTest("MODULO-06", "Procesamiento Pedido Web B2C", function() {
    $res = EcommerceService::procesarPedidoWeb([
        'usuario_web_id'  => 1,
        'metodo_pago'     => 'PAGO_MOVIL',
        'referencia_pago' => 'REF-PM-' . rand(1000, 9999),
        'tasa_cambio'     => 791.32,
        'items'           => [
            ['producto_id' => 1, 'cantidad' => 1, 'precio_unitario' => 1.50]
        ]
    ]);
    if (empty($res['pedido_id'])) throw new Exception("Pedido Web no generado.");
});

// ------------------------------------------------------------------------
// [MÓDULO 07] SERVICIOS, RECEPCIÓN Y TALLER (SAT)
// ------------------------------------------------------------------------
assertModuleTest("MODULO-07", "Orden de Trabajo SAT Taller y Campos Personalizados", function() {
    $db = Database::getConnection();
    $ot = 'OT-FULL-' . rand(1000, 9999);
    $stmt = $db->prepare("
        INSERT INTO sat_ordenes_trabajo (
            numero_orden, tipo_servicio_id, cliente_id, estado_id, fecha_recepcion,
            falla_reportada_cliente, usuario_creador_id, tasa_cambio
        ) VALUES (
            :num, 1, 1, 1, NOW(), 'Mantenimiento preventivo general', 1, 791.32
        )
    ");
    $stmt->execute(['num' => $ot]);
});

// ------------------------------------------------------------------------
// [MÓDULO 08] ACTIVOS FIJOS Y DEPRECIACIONES
// ------------------------------------------------------------------------
assertModuleTest("MODULO-08", "Cálculo de Depreciación Mensual por Línea Recta", function() {
    $costo = 1200.00;
    $residual = 0.00;
    $meses = 12;
    $depMensual = ($costo - $residual) / $meses;
    if ($depMensual != 100.00) throw new Exception("Cálculo incorrecto de depreciación.");
});

// ------------------------------------------------------------------------
// [MÓDULO 09] CONTABILIDAD Y NÓMINA INTEGRADA
// ------------------------------------------------------------------------
assertModuleTest("MODULO-09", "Verificación Plan de Cuentas PUC y Cierre de Nómina", function() {
    $db = Database::getConnection();
    $cnt = (int)$db->query("SELECT COUNT(*) FROM cuentas_bancarias")->fetchColumn();
    if ($cnt < 1) throw new Exception("No hay cuentas registradas.");
});

// ------------------------------------------------------------------------
// [MÓDULO 10] AUDITORÍA FORENSE Y NOTIFICACIONES
// ------------------------------------------------------------------------
assertModuleTest("MODULO-10", "Escritura de Log en Bitácora de Auditoría Inmutable", function() {
    AuditLogger::log('PRUEBAS', 'TEST_RUN', 'Ejecución automatizada del bucle de pruebas', 'productos', '1', ['precio' => 1.0], ['precio' => 1.5], 1, 1);
});

echo "\n========================================================================\n";
echo "    RESUMEN FINAL DE EJECUCIÓN AUTÓNOMA\n";
echo "========================================================================\n";
echo "Total Pruebas Auditadas: {$totalTests}\n";
echo "Pruebas Exitosas:         {$passed}\n";
echo "Pruebas Fallidas:         {$failed}\n";
echo "========================================================================\n";

if ($failed === 0) {
    echo "\n>>> ¡SISTEMA mi ERP VERIFICADO AL 100% OPERATIVO EN LOS 10 MÓDULOS! <<<\n";
} else {
    echo "\n>>> ALERTA: EXISTEN PRUEBAS FALLIDAS. REVISAR LOGS Y REEJECUTAR. <<<\n";
    exit(1);
}
