<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('LOCK_FILE', ROOT_PATH . '/storage/installed.lock');
define('CONFIG_FILE', ROOT_PATH . '/config/database.php');

require_once ROOT_PATH . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\License\LicenseValidator;
use App\Controllers\VentasController;
use App\Controllers\ComprasController;
use App\Controllers\AprovisionamientoController;
use App\Controllers\InventarioController;
use App\Controllers\TesoreriaController;
use App\Controllers\SatController;
use App\Controllers\ContabilidadController;
use App\Controllers\NominaController;
use App\Controllers\ActivosFijosController;
use App\Controllers\EcommerceController;
use App\Controllers\ReportesController;
use App\Controllers\FormatosController;
use App\Controllers\MigracionController;
use App\Controllers\ApiPreventaController;
use App\Controllers\TelegramWebhookController;
use App\Controllers\InstallerController;
use App\Controllers\MaestrosController;
use App\Controllers\ProduccionController;
// 1. Detección de Instalación
$isInstalled = file_exists(LOCK_FILE) && file_exists(CONFIG_FILE);

$router = new Router();

// Rutas del Instalador / Setup Wizard (Disponibles siempre)
$router->get('installer', function() {
    require ROOT_PATH . '/views/installer/wizard.php';
});
$router->get('instalar', function() {
    require ROOT_PATH . '/views/installer/wizard.php';
});
$router->get('api/installer/requirements', [InstallerController::class, 'checkReq']);
$router->post('api/installer/database', [InstallerController::class, 'dbSetup']);
$router->post('api/installer/license', [InstallerController::class, 'licenseUpload']);
$router->post('api/installer/admin', [InstallerController::class, 'adminSetup']);

if (!$isInstalled) {
    $router->get('', function() {
        header('Location: /installer');
        exit;
    });

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    $router->dispatch($uri, $method);
    exit;
}

// 2. Validación de Licencia
$pubKey = ROOT_PATH . '/config/keys/license_public_key.pem';
if (!file_exists($pubKey)) {
    $pubKey = ROOT_PATH . '/config/keys/license_public.key';
}
$licFile = ROOT_PATH . '/license/sistema.lic';
if (!file_exists($licFile)) {
    $licFile = ROOT_PATH . '/storage/lic/license.lic';
}

$licenciaActiva = null;
if (file_exists($pubKey) && file_exists($licFile)) {
    try {
        $validator = new LicenseValidator($pubKey);
        $licenciaActiva = $validator->validate($licFile);
    } catch (\Throwable $e) {
        $licError = $e->getMessage();
    }
}

// 3. Registro de Vistas Web (Frontend)
$router->view('', 'ventas/pos.php');
$router->view('ventas/pos', 'ventas/pos.php');
$router->view('pos', 'ventas/pos.php');
// Módulo de Ventas: Vistas individuales por tipo de documento
$router->view('ventas', 'ventas/facturacion.php');
$router->view('ventas/panel', 'ventas/facturacion.php');
$router->view('ventas/facturacion', 'ventas/facturacion.php');
$router->view('ventas/devoluciones', 'ventas/devoluciones.php');
$router->view('ventas/pedidos', 'ventas/pedidos.php');
$router->view('ventas/presupuestos', 'ventas/presupuestos.php');
// Módulo de Compras: Circuito completo
$router->view('compras/nueva', 'compras/nueva_compra.php');
$router->view('compras/orden', 'compras/orden_compra.php');
$router->view('compras/recepcion', 'compras/recepcion.php');
$router->view('compras/sugerencias', 'compras/asistente_sugerencia.php');
$router->view('sat/panel', 'sat/panel_servicios.php');
$router->view('contabilidad/panel', 'contabilidad/panel_contable.php');
$router->view('contador/multicliente', 'contador/panel_multicliente.php');
$router->view('nomina/panel', 'nomina/panel_nomina.php');
$router->view('activos/panel', 'activos/panel_activos.php');
$router->view('produccion/panel', 'produccion/panel_produccion.php');
$router->view('reportes/suite', 'reportes/suite_mi.php');
$router->view('configuracion/canales-pasarelas', 'configuracion/canales_pasarelas.php');
$router->view('configuracion/formatos', 'configuracion/editor_formatos.php');
$router->view('configuracion/migracion', 'configuracion/migracion_masiva.php');
$router->view('configuracion/whatsapp', 'configuracion/whatsapp_config.php');
$router->view('configuracion/respaldos', 'configuracion/respaldos_sync.php');
$router->view('auditoria/logs', 'auditoria/visor_logs.php');
$router->view('tienda', 'ecommerce/tienda.php');
$router->view('tienda/checkout', 'ecommerce/checkout_bancario.php');
$router->view('tienda/pedido-exitoso', 'ecommerce/pedido_exitoso.php');
$router->view('preventa/app', 'preventa/app.php');

// Vistas de Archivos Maestros
$router->view('maestros', 'maestros/panel_maestros.php');
$router->view('maestros/productos', 'maestros/productos.php');
$router->view('maestros/clientes', 'maestros/clientes.php');
$router->view('maestros/proveedores', 'maestros/proveedores.php');
$router->view('maestros/departamentos', 'maestros/departamentos.php');
$router->view('maestros/vendedores', 'maestros/vendedores.php');
$router->view('maestros/zonas', 'maestros/zonas.php');
$router->view('maestros/monedas', 'maestros/monedas.php');
$router->view('maestros/depositos', 'maestros/depositos.php');
$router->view('maestros/bancos', 'maestros/bancos.php');

// 4. Registro de Endpoints API

// Archivos Maestros CRUDs
$router->get('api/maestros/resumen', [MaestrosController::class, 'getResumen']);
$router->get('api/maestros/clientes', [MaestrosController::class, 'getClientes']);
$router->post('api/maestros/clientes', [MaestrosController::class, 'guardarCliente']);
$router->get('api/maestros/proveedores', [MaestrosController::class, 'getProveedores']);
$router->post('api/maestros/proveedores', [MaestrosController::class, 'guardarProveedor']);
$router->get('api/maestros/productos', [MaestrosController::class, 'getProductos']);
$router->post('api/maestros/productos', [MaestrosController::class, 'guardarProducto']);
$router->get('api/maestros/departamentos', [MaestrosController::class, 'getDepartamentos']);
$router->post('api/maestros/departamentos', [MaestrosController::class, 'guardarDepartamento']);
$router->post('api/maestros/categorias', [MaestrosController::class, 'guardarCategoria']);
$router->get('api/maestros/vendedores', [MaestrosController::class, 'getVendedores']);
$router->post('api/maestros/vendedores', [MaestrosController::class, 'guardarVendedor']);
$router->get('api/maestros/zonas', [MaestrosController::class, 'getZonas']);
$router->post('api/maestros/zonas', [MaestrosController::class, 'guardarZona']);
$router->get('api/maestros/monedas', [MaestrosController::class, 'getMonedas']);
$router->post('api/maestros/monedas/tasa', [MaestrosController::class, 'actualizarTasa']);
$router->get('api/maestros/depositos', [MaestrosController::class, 'getDepositos']);
$router->post('api/maestros/depositos', [MaestrosController::class, 'guardarDeposito']);
$router->get('api/maestros/bancos', [MaestrosController::class, 'getBancos']);
$router->post('api/maestros/bancos', [MaestrosController::class, 'guardarBanco']);

// Ventas & POS
$router->get('api/pos/resumen-cierre-z', [VentasController::class, 'resumenCierreZ']);
$router->post('api/pos/procesar-venta', [VentasController::class, 'procesarVenta']);
// Ventas: Historial, Conversión de Documentos e Impresión
$router->get('api/ventas/historial', [VentasController::class, 'historial']);
$router->get('api/ventas/{id}/documento', [VentasController::class, 'imprimirDocumento']);
$router->post('api/ventas/{id}/convertir', [VentasController::class, 'convertirDocumento']);
$router->post('api/ventas/{id}/anular', [VentasController::class, 'anularDocumento']);

// Preventa Móvil
$router->get('api/preventa/sincronizar', [ApiPreventaController::class, 'sincronizarCatalogo']);
$router->post('api/preventa/enviar-pedido', [ApiPreventaController::class, 'enviarPedido']);

// Compras & Aprovisionamiento
$router->get('api/compras/sugerencias', [AprovisionamientoController::class, 'consultarSugerencias']);
$router->post('api/compras/orden-automatica', [AprovisionamientoController::class, 'generarOrdenAutomatica']);
$router->post('api/compras/procesar-factura', [ComprasController::class, 'procesarFactura']);
$router->post('api/compras/aprovisionar', [ComprasController::class, 'aprovisionar']);
// Órdenes de Compra (Manual)
$router->get('api/compras/ordenes', [ComprasController::class, 'listarOrdenes']);
$router->post('api/compras/orden', [ComprasController::class, 'crearOrden']);
$router->get('api/compras/ordenes/pendientes', [ComprasController::class, 'ordenesPendientes']);
$router->post('api/compras/recepcion', [ComprasController::class, 'procesarRecepcion']);
$router->get('api/compras/{id}/documento', [ComprasController::class, 'imprimirDocumento']);
$router->get('api/compras/historial', [ComprasController::class, 'historial']);

// Inventario & Kardex
$router->get('api/inventario/productos', [InventarioController::class, 'listarProductos']);
$router->get('api/inventario/producto/{id}', [InventarioController::class, 'verProducto']);
$router->get('api/inventario/kardex/{id}', [InventarioController::class, 'verKardex']);
$router->post('api/inventario/ajuste', [InventarioController::class, 'ajusteStock']);
$router->post('api/inventario/traslado', [InventarioController::class, 'traslado']);
$router->get('api/inventario/depositos', [InventarioController::class, 'listarDepositos']);
$router->get('api/inventario/categorias', [InventarioController::class, 'listarCategorias']);

// Producción / BOM
$router->get('api/produccion/formulas', [ProduccionController::class, 'listarFormulas']);
$router->post('api/produccion/formulas', [ProduccionController::class, 'guardarFormula']);
$router->get('api/produccion/ordenes', [ProduccionController::class, 'listarOrdenes']);
$router->post('api/produccion/orden', [ProduccionController::class, 'iniciarOrden']);
$router->post('api/produccion/completar', [ProduccionController::class, 'completarOrden']);

// Tesorería & Bancos
$router->post('api/tesoreria/c2p-debitar', [TesoreriaController::class, 'debitarC2P']);
$router->get('api/bancos/cuentas', function() {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => \App\Models\BancoService::obtenerCuentas()]);
});
$router->post('api/bancos/transferir', function() {
    header('Content-Type: application/json');
    $in = json_decode((string)file_get_contents('php://input'), true) ?? [];
    try {
        $res = \App\Models\BancoService::transferir(
            (int)$in['cuenta_origen_id'],
            (int)$in['cuenta_destino_id'],
            (float)$in['monto'],
            $in['numero_referencia'] ?? ('TR-' . time()),
            $in['concepto'] ?? 'Transferencia interbancaria'
        );
        echo json_encode($res);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// SAT (Servicio de Asistencia Técnica / Taller)
$router->get('api/sat/ordenes', [SatController::class, 'listarOrdenes']);
$router->post('api/sat/crear', [SatController::class, 'crearOrden']);
$router->post('api/sat/cambiar-estado', [SatController::class, 'cambiarEstado']);
$router->post('api/sat/actualizar', [SatController::class, 'actualizarOrden']);
$router->post('api/sat/facturar', [SatController::class, 'facturarOrden']);

// Contabilidad
$router->get('api/contabilidad/puc', [ContabilidadController::class, 'planCuentas']);
$router->get('api/contabilidad/plan-cuentas', [ContabilidadController::class, 'planCuentas']);
$router->get('api/contabilidad/balance', [ContabilidadController::class, 'balanceComprobacion']);
$router->get('api/contabilidad/asientos', [ContabilidadController::class, 'asientos']);
$router->post('api/contabilidad/asientos', [ContabilidadController::class, 'crearAsientoManual']);
$router->get('api/contabilidad/asientos/{id}/detalles', function(int $id) {
    (new \App\Controllers\ContabilidadController())->detalleAsiento($id);
});
$router->post('api/contabilidad/asiento-manual', [ContabilidadController::class, 'crearAsientoManual']);

// Nómina
$router->get('api/nomina/empleados', [NominaController::class, 'listarEmpleados']);
$router->post('api/nomina/empleados', [NominaController::class, 'guardarEmpleado']);
$router->put('api/nomina/empleados/{id}', [NominaController::class, 'guardarEmpleado']);
$router->post('api/nomina/prenomina', [NominaController::class, 'prenomina']);
$router->post('api/nomina/cierre', [NominaController::class, 'procesarCierre']);
$router->get('api/nomina/recibo/{id}', [NominaController::class, 'reciboPdf']);

// Activos Fijos
$router->get('api/activos-fijos', [ActivosFijosController::class, 'listarActivos']);
$router->post('api/activos-fijos/registrar', [ActivosFijosController::class, 'registrarActivo']);
$router->post('api/activos-fijos/depreciar-mensual', [ActivosFijosController::class, 'depreciarMensual']);

// E-commerce
$router->get('api/ecommerce/catalogo', [EcommerceController::class, 'catalogo']);
$router->post('api/ecommerce/pedido', [EcommerceController::class, 'crearPedidoWeb']);
$router->post('api/ecommerce/facturar', [EcommerceController::class, 'facturarPedido']);
$router->get('api/ecommerce/pedidos-por-facturar', [EcommerceController::class, 'pedidosPorFacturar']);
$router->post('api/ecommerce/pago-c2p', function() {
    header('Content-Type: application/json');
    try {
        $in = json_decode((string)file_get_contents('php://input'), true) ?? [];
        if (empty($in['telefono_pagador']) || empty($in['cedula_pagador']) || empty($in['token_otp'])) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Datos del pago C2P incompletos.']);
            return;
        }
        $res = \App\Core\BankGatewayEngine::procesarDebitoInmediato(
            (string)$in['banco_codigo'],
            [
                'banco_origen'     => (string)$in['banco_origen'],
                'telefono_pagador' => (string)$in['telefono_pagador'],
                'cedula_pagador'   => (string)$in['cedula_pagador'],
                'token_otp'        => (string)$in['token_otp'],
                'monto_bs'         => (float)($in['monto_bs'] ?? 0),
                'tasa_cambio'      => (float)($in['tasa_cambio'] ?? \App\Core\Database::getTasaActualUsd()),
                'pedido_web_id'    => (int)($in['pedido_web_id'] ?? 0),
                'numero_orden'     => (string)($in['numero_orden'] ?? '')
            ]
        );
        echo json_encode(array_merge(['status' => 'success'], $res));
    } catch (\Throwable $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// Suite de Reportes
$router->get('api/reportes/{tipo}', [ReportesController::class, 'consultar']);

// Formatos de Impresión
$router->get('api/formatos/listar', [FormatosController::class, 'listarFormatos']);
$router->post('api/formatos/guardar', [FormatosController::class, 'guardarFormato']);
$router->post('api/formatos/renderizar', [FormatosController::class, 'renderizarPrevio']);

// Migración Masiva CSV
$router->get('api/migracion/plantilla/{entidad}', [MigracionController::class, 'descargarPlantilla']);
$router->post('api/migracion/analizar', [MigracionController::class, 'analizarArchivo']);
$router->post('api/migracion/ejecutar', [MigracionController::class, 'ejecutarMigracion']);

// Webhooks
$router->post('webhook/telegram', [TelegramWebhookController::class, 'procesarWebhook']);

// Clientes y Proveedores Auxiliares
$router->get('api/clientes', function() {
    header('Content-Type: application/json');
    $db = \App\Core\Database::getConnection();
    echo json_encode(['status' => 'success', 'data' => $db->query("SELECT * FROM clientes WHERE estado = 1 ORDER BY razon_social ASC")->fetchAll()]);
});
$router->get('api/proveedores', function() {
    header('Content-Type: application/json');
    $db = \App\Core\Database::getConnection();
    echo json_encode(['status' => 'success', 'data' => $db->query("SELECT * FROM proveedores WHERE estado = 1 ORDER BY razon_social ASC")->fetchAll()]);
});

// Monedas Tasas
$router->get('api/monedas/tasas', function() {
    header('Content-Type: application/json');
    try {
        $db = \App\Core\Database::getConnection();
        $tasas = $db->query("SELECT * FROM monedas")->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $tasas]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// CxC - Estado de cuenta por cliente
$router->get('api/maestros/clientes/{id}/cxc', function(int $id) {
    header('Content-Type: application/json');
    try {
        $db = \App\Core\Database::getConnection();
        $stmt = $db->prepare("
            SELECT v.id, v.numero_factura, v.fecha_emision, v.fecha_vencimiento,
                   v.total_usd AS monto_total, v.saldo_pendiente_usd AS saldo_pendiente,
                   CASE WHEN v.fecha_vencimiento < CURDATE() AND v.saldo_pendiente_usd > 0 THEN 1 ELSE 0 END AS vencido
            FROM ventas v
            WHERE v.cliente_id = :id AND v.condicion_pago = 'CREDITO'
              AND v.saldo_pendiente_usd > 0 AND v.estado != 'ANULADA'
            ORDER BY v.fecha_vencimiento ASC
        ");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// CxP - Estado de cuenta por proveedor
$router->get('api/maestros/proveedores/{id}/cxp', function(int $id) {
    header('Content-Type: application/json');
    try {
        $db = \App\Core\Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.id, c.numero_factura, c.fecha_emision, c.fecha_vencimiento,
                   c.total_general, c.saldo_pendiente,
                   CASE WHEN c.fecha_vencimiento < CURDATE() AND c.saldo_pendiente > 0 THEN 1 ELSE 0 END AS vencido
            FROM compras c
            WHERE c.proveedor_id = :id AND c.saldo_pendiente > 0 AND c.estado != 'ANULADA'
            ORDER BY c.fecha_vencimiento ASC
        ");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// Clasificación jerárquica 3 niveles: Departamento -> Familia -> Categoría
$router->get('api/maestros/clasificacion', function() {
    header('Content-Type: application/json');
    try {
        $db = \App\Core\Database::getConnection();
        $deptos = $db->query("SELECT id, codigo, descripcion, descripcion AS nombre FROM departamentos ORDER BY descripcion ASC")->fetchAll();
        $familias = [];
        if ($db->query("SHOW TABLES LIKE 'familias'")->rowCount() > 0) {
            $familias = $db->query("SELECT id, codigo, descripcion AS nombre, departamento_id FROM familias ORDER BY descripcion ASC")->fetchAll();
        }
        $cats = $db->query("SELECT id, descripcion, departamento_id FROM categorias ORDER BY descripcion ASC")->fetchAll();
        echo json_encode(['status' => 'success', 'departamentos' => $deptos, 'familias' => $familias, 'categorias' => $cats]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// Stock de producto por depósito
$router->get('api/maestros/productos/{id}/stock', function(int $id) {
    header('Content-Type: application/json');
    try {
        $db = \App\Core\Database::getConnection();
        $stmt = $db->prepare("
            SELECT d.id AS deposito_id, d.descripcion AS deposito_nombre,
                   COALESCE(ie.existencia, 0) AS existencia,
                   COALESCE(ie.existencia_comprometida, 0) AS comprometida,
                   COALESCE(ie.punto_reorden, 0) AS stock_minimo,
                   9999 AS stock_maximo
            FROM depositos d
            LEFT JOIN inventario_existencias ie ON ie.producto_id = :id AND ie.deposito_id = d.id
            WHERE d.estado = 1
            ORDER BY d.descripcion ASC
        ");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// Configuración de Canales (GET / POST)
$router->get('api/configuracion/canales', function() {
    header('Content-Type: application/json');
    try {
        $db = \App\Core\Database::getConnection();
        $stmt = $db->query("SELECT clave, valor FROM configuracion WHERE clave LIKE 'canal_%' OR clave LIKE 'whatsapp_%' OR clave LIKE 'telegram_%' OR clave LIKE 'pasarela_%'");
        $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        // Decode JSON values
        $cfg = [];
        foreach ($rows as $k => $v) {
            $decoded = json_decode($v, true);
            $cfg[$k] = $decoded !== null ? $decoded : $v;
        }
        echo json_encode(['status' => 'success', 'data' => $cfg]);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'success', 'data' => []]);
    }
});
$router->post('api/configuracion/canales', function() {
    header('Content-Type: application/json');
    try {
        $db = \App\Core\Database::getConnection();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (:k, :v) ON DUPLICATE KEY UPDATE valor = :v2");
        foreach ($input as $seccion => $data) {
            $stmt->execute([':k' => 'canal_' . $seccion, ':v' => json_encode($data), ':v2' => json_encode($data)]);
        }
        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Configuración guardada.']);
    } catch (\Exception $e) {
        if (isset($db)) $db->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

// Test WhatsApp
$router->post('api/configuracion/test-whatsapp', function() {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $telefono = preg_replace('/\D/', '', $input['telefono'] ?? '');
    if (empty($telefono)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Número de teléfono requerido.']);
        return;
    }
    // Meta Cloud API test
    $config = $input['config'] ?? [];
    if (($config['modo'] ?? '') === 'META_CLOUD_API' && !empty($config['phone_number_id']) && !empty($config['access_token'])) {
        $ch = curl_init("https://graph.facebook.com/v18.0/{$config['phone_number_id']}/messages");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $config['access_token'], 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['messaging_product' => 'whatsapp', 'to' => $telefono, 'type' => 'text', 'text' => ['body' => '✅ Prueba de conexión desde mi ERP. Canal WhatsApp activo.']])
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200) {
            echo json_encode(['status' => 'success', 'message' => 'Mensaje enviado via Meta API.']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Error Meta API: ' . $resp]);
        }
    } else {
        // Desktop mode - just validate number format
        echo json_encode(['status' => 'success', 'message' => 'Modo Desktop activo. Abriría https://wa.me/' . $telefono]);
    }
});

// Test Telegram
$router->post('api/configuracion/test-telegram', function() {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = $input['bot_token'] ?? '';
    $chatId = $input['chat_id'] ?? '';
    if (empty($token) || empty($chatId)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Token y Chat ID requeridos.']);
        return;
    }
    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['chat_id' => $chatId, 'text' => '✅ *mi ERP* — Prueba de conexión exitosa. Bot Telegram activo.', 'parse_mode' => 'Markdown']
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200) {
        echo json_encode(['status' => 'success', 'message' => 'Mensaje enviado correctamente.']);
    } else {
        http_response_code(400);
        $decoded = json_decode($resp, true);
        echo json_encode(['status' => 'error', 'message' => $decoded['description'] ?? 'Error Telegram API.']);
    }
});

// Registrar Webhook Telegram
$router->post('api/configuracion/telegram-webhook', function() {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = $input['bot_token'] ?? '';
    $webhookUrl = $input['webhook_url'] ?? '';
    if (empty($token) || empty($webhookUrl)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Token y Webhook URL requeridos.']);
        return;
    }
    $ch = curl_init("https://api.telegram.org/bot{$token}/setWebhook");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => ['url' => $webhookUrl]]);
    $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $decoded = json_decode($resp, true);
    if ($decoded['ok'] ?? false) {
        echo json_encode(['status' => 'success', 'message' => 'Webhook registrado: ' . ($decoded['description'] ?? '')]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $decoded['description'] ?? 'Error.']);
    }
});

// Despacho de la petición
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($uri, $method);

