<?php
// database/seeders/DemoSeeder.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Database;

class DemoSeeder {
    public static function run(): void {
        $db = Database::getConnection();
        echo "Iniciando siembra de datos de demostración...\n";
        $db->exec("SET FOREIGN_KEY_CHECKS=0;");

        // 1. Limpieza de tablas principales
        $tablas = [
            'productos_presentaciones', 'producto_seriales', 'producto_lotes', 'producto_deposito', 'productos',
            'categorias', 'familias', 'departamentos', 'depositos', 'clientes', 'proveedores',
            'cuentas_bancarias', 'ventas', 'ventas_detalles', 'ventas_detalles_seriales',
            'compras', 'compras_detalles', 'recibos_cobranza', 'recibos_cobranza_formas_pago',
            'nomina_empleados', 'nomina_periodos', 'nomina_recibos', 'nomina_recibos_detalles',
            'produccion_formulas', 'produccion_formulas_detalles', 'produccion_ordenes',
            'sat_ordenes_trabajo', 'sat_ordenes_detalles', 'sat_ordenes_valores_campos',
            'activos_fijos', 'activos_depreciaciones_mensuales', 'ecommerce_usuarios', 'ecommerce_pedidos'
        ];
        foreach ($tablas as $t) {
            try {
                $db->exec("TRUNCATE TABLE `{$t}`;");
            } catch (\Throwable $e) {}
        }
        $db->exec("SET FOREIGN_KEY_CHECKS=1;");

        // 2. Estructura de Catálogo y Depósitos
        echo "-> Creando catálogo, departamentos y depósitos...\n";
        $db->exec("
            INSERT INTO depositos (id, codigo, descripcion, estado) VALUES
            (1, 'DEP-01', 'Almacén Principal / Tienda', 1),
            (2, 'DEP-02', 'Depósito Mayorista / Cuarentena', 1)
            ON DUPLICATE KEY UPDATE id=id;

            INSERT INTO departamentos (id, codigo, descripcion, estado) VALUES
            (1, 'DEP-ALIM', 'Alimentos y Víveres', 1),
            (2, 'DEP-ELEC', 'Tecnología y Electrónica', 1),
            (3, 'DEP-FERR', 'Ferretería y Herramientas', 1)
            ON DUPLICATE KEY UPDATE id=id;

            INSERT INTO categorias (id, departamento_id, codigo, descripcion, estado) VALUES
            (1, 1, 'CAT-ENL', 'Atún y Pescados Enlatados', 1),
            (2, 2, 'CAT-CEL', 'Celulares Inteligentes', 1),
            (3, 3, 'CAT-LAP', 'Laptops y Portátiles', 1)
            ON DUPLICATE KEY UPDATE id=id;

            INSERT INTO unidades_medida (id, codigo, nombre, decimales) VALUES
            (1, 'UND', 'Unidad', 0),
            (2, 'KG', 'Kilogramo', 2)
            ON DUPLICATE KEY UPDATE id=id;

            INSERT INTO cuentas_bancarias (id, nombre_banco, numero_cuenta, moneda_id, saldo_actual, estado) VALUES
            (1, 'Banesco Banco Universal', '0134-0001-22-1234567890', 1, 125000.0000, 1),
            (2, 'Banco Plaza (Cuenta Custodia USD)', '0138-0002-33-0987654321', 2, 4500.0000, 1)
            ON DUPLICATE KEY UPDATE id=id;
        ");

        // 3. Productos
        echo "-> Insertando productos maestros...\n";
        $db->exec("
            INSERT INTO productos (id, codigo, codigo_barra, descripcion, categoria_id, unidad_medida_id, costo_ultimo, costo_promedio, precio_a, precio_b, precio_c, precio_d, porcentaje_iva, exento_iva, estado) VALUES
            (1, 'ALIM-001', '7591234567890', 'Atún en Aceite Margarita 140g', 1, 1, 0.9000, 0.9000, 1.5000, 1.4000, 1.3500, 1.3000, 16.00, 0, 1),
            (2, 'CEL-001', '6941234567891', 'Smartphone Xiaomi Redmi Note 13 Pro 256GB', 2, 1, 160.0000, 160.0000, 220.0000, 210.0000, 200.0000, 190.0000, 16.00, 0, 1),
            (3, 'INS-HARINA', '7590001', 'Saco Harina de Trigo Panadera 50kg', 1, 1, 32.0000, 32.0000, 40.0000, 38.0000, 36.0000, 35.0000, 0.00, 1, 1)
            ON DUPLICATE KEY UPDATE id=id;

            INSERT INTO producto_deposito (producto_id, deposito_id, existencia, existencia_comprometida, punto_reorden) VALUES
            (1, 1, 480.0000, 0.0000, 50.0000),
            (2, 1, 5.0000, 0.0000, 2.0000),
            (3, 1, 20.0000, 0.0000, 5.0000)
            ON DUPLICATE KEY UPDATE existencia=VALUES(existencia);
        ");

        // 4. Clientes y Proveedores
        echo "-> Creando clientes comerciales y proveedores...\n";
        $db->exec("
            INSERT INTO zonas (id, codigo, descripcion, estado) VALUES
            (1, 'ZONA-01', 'Zona Central Valencia', 1)
            ON DUPLICATE KEY UPDATE id=id;

            INSERT INTO vendedores (id, codigo, nombre, estado) VALUES
            (1, 'VEND-01', 'Vendedor Principal', 1)
            ON DUPLICATE KEY UPDATE id=id;

            INSERT INTO clientes (id, codigo, razon_social, documento_fiscal, direccion_fiscal, telefono, email, zona_id, limite_credito, saldo_actual, estado) VALUES
            (1, 'CLI-001', 'COMERCIAL LOS ANDES C.A.', 'J-31245678-0', 'Av. Bolívar, Centro Comercial Valencia Local 12', '0241-8887766', 'contacto@losandes.com', 1, 5000.00, 1200.00, 1),
            (2, 'CLI-002', 'INVERSIONES EL MAYORISTA 2026 S.A.', 'J-40987654-1', 'Zona Industrial Castillito, Galpón 4', '0241-5554433', 'ventas@elmayorista.com', 1, 10000.00, 0.00, 1)
            ON DUPLICATE KEY UPDATE id=id;

            INSERT INTO proveedores (id, codigo, razon_social, documento_fiscal, direccion_fiscal, telefono, email, estado) VALUES
            (1, 'PROV-001', 'ALIMENTOS POLAR COMERCIAL C.A.', 'J-00041312-9', 'Caracas, Los Cortijos de Lourdes', '0212-2028111', 'pedidos@empresaspolar.com', 1),
            (2, 'PROV-002', 'XIAOMI VENEZUELA DISTRIBUIDOR C.A.', 'J-50123987-4', 'Caracas, Las Mercedes', '0212-9998877', 'distribuidores@xiaomive.com', 1)
            ON DUPLICATE KEY UPDATE id=id;
        ");

        echo "\n============================================================\n";
        echo "¡Siembra de datos de demostración completada con éxito!\n";
        echo "Catálogo, existencias, clientes y proveedores listos.\n";
        echo "============================================================\n";
    }
}

if (php_sapi_name() === 'cli') {
    DemoSeeder::run();
}