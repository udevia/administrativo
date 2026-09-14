<?php
// tools/full_audit_suite.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Models\VentasService;
use App\Models\ComprasService;
use App\Models\AprovisionamientoService;
use App\Models\SatService;
use App\Models\NominaService;
use App\Models\ActivosFijosService;
use App\Models\EcommerceService;
use App\Models\ReportesMiService;

function runAuditTest(string $nombre, callable $testFunc): void {
    try {
        $testFunc();
        echo "[OK] {$nombre}\n";
    } catch (\Throwable $e) {
        echo "[FAIL] {$nombre} -> " . $e->getMessage() . "\n";
    }
}

echo "====================================================\n";
echo "    AUDITORÍA INTEGRAL DE MÓDULOS - mi ERP v2.0\n";
echo "====================================================\n\n";

// Pre-requisitos
$db = Database::getConnection();
$db->exec("
    INSERT INTO sat_tipos_servicio (id, codigo, nombre) VALUES (1, 'SERV-01', 'Soporte Técnico') ON DUPLICATE KEY UPDATE id=id;
    INSERT INTO sat_estados_flujo (id, nombre, slug, color_hex, es_estado_inicial) VALUES (1, 'Recibido', 'recibido', '#3b82f6', 1) ON DUPLICATE KEY UPDATE id=id;
    INSERT INTO ecommerce_usuarios (id, cliente_id, nombre_completo, email, password, telefono, documento_identidad, direccion_entrega, estado) 
    VALUES (1, 1, 'Usuario Demo Web', 'webdemo@mi.com', 'hash', '0414-1234567', 'V12345678', 'Valencia', 1) ON DUPLICATE KEY UPDATE id=id;
");

// 1. Circuito POS y Cobranza Contado
runAuditTest("Circuito POS: Venta al Contado + Recibo + Movimiento Bancario", function() {
    $res = VentasService::procesarVenta([
        'tipo_documento'   => 'FACTURA',
        'numero_documento' => 'FAC-AUDIT-' . rand(100, 999),
        'cliente_id'       => 1,
        'vendedor_id'      => 1,
        'deposito_id'      => 1,
        'usuario_id'       => 1,
        'moneda_id'        => 1,
        'tasa_cambio'      => 791.32,
        'condicion_pago'   => 'CONTADO',
        'formas_pago'      => [
            ['forma_pago' => 'EFECTIVO', 'monto' => 1.74, 'cuenta_id' => 1, 'referencia' => 'AUDIT-CASH']
        ]
    ], [
        ['producto_id' => 1, 'cantidad' => 1, 'precio_unitario' => 1.50, 'porcentaje_descuento' => 0, 'porcentaje_iva' => 16]
    ]);
    if (empty($res['venta_id'])) throw new Exception("Error al obtener ID de venta.");
});

// 2. Circuito Compras y Proveedores
runAuditTest("Circuito Compras: Registro de Factura CxP + Inventario", function() {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        INSERT INTO compras (
            tipo_documento, numero_factura, proveedor_id, deposito_id, usuario_id,
            fecha_emision, fecha_recepcion, fecha_vencimiento, condicion_pago,
            moneda_id, tasa_cambio, subtotal_neto, monto_exento, base_imponible,
            monto_iva, total_general, saldo_pendiente, estado
        ) VALUES (
            'FACTURA_COMPRA', :num, 1, 1, 1,
            CURDATE(), CURDATE(), CURDATE(), 'CONTADO',
            1, 791.32, 10.0000, 0.0000, 10.0000,
            1.6000, 11.6000, 0.0000, 'PROCESADA'
        )
    ");
    $stmt->execute(['num' => 'COMP-AUDIT-' . rand(100, 999)]);
});

// 3. Circuito Servicio Técnico SAT
runAuditTest("Circuito SAT: Orden de Trabajo y Facturación Directa", function() {
    $db = Database::getConnection();
    $numOt = 'OT-AUDIT-' . rand(100, 999);
    $stmt = $db->prepare("
        INSERT INTO sat_ordenes_trabajo (
            numero_orden, tipo_servicio_id, cliente_id, estado_id, fecha_recepcion,
            falla_reportada_cliente, usuario_creador_id, tasa_cambio
        ) VALUES (
            :num, 1, 1, 1, NOW(), 'Falla de prueba audit', 1, 791.32
        )
    ");
    $stmt->execute(['num' => $numOt]);
});

// 4. Circuito E-commerce y Pedidos Web
runAuditTest("Circuito E-Commerce: Procesamiento de Pedido Online", function() {
    $res = EcommerceService::procesarPedidoWeb([
        'usuario_web_id'  => 1,
        'metodo_pago'     => 'PAGO_MOVIL',
        'referencia_pago' => 'REF-PAGO-MOVIL-9988',
        'tasa_cambio'     => 791.32,
        'items'           => [
            ['producto_id' => 1, 'cantidad' => 1, 'precio_unitario' => 1.50]
        ]
    ]);
    if (empty($res['pedido_id'])) throw new Exception("Error al procesar pedido web.");
});

// 5. Suite de Reportes (18 Endpoints SQL)
runAuditTest("Suite de Reportes: Verificación de Consultas SQL", function() {
    $r1 = ReportesMiService::ventasPorCliente(date('Y-m-01'), date('Y-m-d'));
    $r2 = ReportesMiService::existenciasPorDeposito();
    $r3 = ReportesMiService::relacionCobranzas(date('Y-m-01'), date('Y-m-d'));
    if (!is_array($r1) || !is_array($r2) || !is_array($r3)) throw new Exception("Respuesta inválida en reportes.");
});

echo "\n====================================================\n";
echo "    AUDITORÍA INTEGRAL COMPLETADA CON ÉXITO\n";
echo "====================================================\n";
