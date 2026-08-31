<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use Exception;
use PDO;

class MaestrosController {
    /**
     * Resumen general de conteos de registros maestros
     */
    public function getResumen(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $resumen = [
                'total_productos'     => (int)$db->query("SELECT COUNT(*) FROM productos WHERE estado = 1")->fetchColumn(),
                'total_clientes'      => (int)$db->query("SELECT COUNT(*) FROM clientes WHERE estado = 1")->fetchColumn(),
                'total_proveedores'   => (int)$db->query("SELECT COUNT(*) FROM proveedores WHERE estado = 1")->fetchColumn(),
                'total_departamentos' => (int)$db->query("SELECT COUNT(*) FROM departamentos WHERE estado = 1")->fetchColumn(),
                'total_categorias'    => (int)$db->query("SELECT COUNT(*) FROM categorias WHERE estado = 1")->fetchColumn(),
                'total_vendedores'    => (int)$db->query("SELECT COUNT(*) FROM vendedores WHERE estado = 1")->fetchColumn(),
                'total_zonas'         => (int)$db->query("SELECT COUNT(*) FROM zonas WHERE estado = 1")->fetchColumn(),
                'total_monedas'       => (int)$db->query("SELECT COUNT(*) FROM monedas")->fetchColumn(),
                'total_depositos'     => (int)$db->query("SELECT COUNT(*) FROM depositos WHERE estado = 1")->fetchColumn(),
                'total_cuentas_banco' => (int)$db->query("SELECT COUNT(*) FROM cuentas_bancarias WHERE estado = 1")->fetchColumn()
            ];

            echo json_encode(['status' => 'success', 'data' => $resumen]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // CLIENTES
    // =========================================================================
    public function getClientes(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $q = trim($_GET['q'] ?? '');
            $sql = "
                SELECT c.*, z.descripcion AS zona_nombre, v.nombre AS vendedor_nombre
                FROM clientes c
                LEFT JOIN zonas z ON c.zona_id = z.id
                LEFT JOIN vendedores v ON c.vendedor_id = v.id
            ";
            if ($q !== '') {
                $sql .= " WHERE c.razon_social LIKE :q OR c.codigo LIKE :q OR c.documento_fiscal LIKE :q";
            }
            $sql .= " ORDER BY c.razon_social ASC LIMIT 200";

            $stmt = $db->prepare($sql);
            if ($q !== '') {
                $stmt->execute(['q' => "%{$q}%"]);
            } else {
                $stmt->execute();
            }
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarCliente(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $codigo = trim($input['codigo'] ?? 'CLI-' . rand(1000, 9999));
            $razon = trim($input['razon_social'] ?? '');
            $doc = trim($input['documento_fiscal'] ?? '');

            if ($razon === '' || $doc === '') {
                throw new Exception("La razón social y el documento fiscal (RIF/Cédula) son obligatorios.");
            }

            if ($id) {
                $stmt = $db->prepare("
                    UPDATE clientes SET
                        codigo = :cod, razon_social = :raz, nombre_comercial = :nom_c, documento_fiscal = :doc,
                        tipo_persona = :tp, tipo_contribuyente = :tc, direccion_fiscal = :dir, telefono = :tel,
                        email = :em, zona_id = :zon, vendedor_id = :vend, lista_precio_default = :lp,
                        limite_credito = :lc, dias_credito = :dc, permite_credito = :pc,
                        aplica_retencion_iva = :ar_iva, porcentaje_retencion_iva = :pr_iva,
                        aplica_retencion_islr = :ar_islr, estado = :est
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'      => $id,
                    'cod'     => $codigo,
                    'raz'     => $razon,
                    'nom_c'   => $input['nombre_comercial'] ?? null,
                    'doc'     => $doc,
                    'tp'      => $input['tipo_persona'] ?? 'juridica',
                    'tc'      => $input['tipo_contribuyente'] ?? 'ordinario',
                    'dir'     => $input['direccion_fiscal'] ?? 'N/A',
                    'tel'     => $input['telefono'] ?? null,
                    'em'      => $input['email'] ?? null,
                    'zon'     => !empty($input['zona_id']) ? (int)$input['zona_id'] : 1,
                    'vend'    => !empty($input['vendedor_id']) ? (int)$input['vendedor_id'] : null,
                    'lp'      => $input['lista_precio_default'] ?? 'A',
                    'lc'      => (float)($input['limite_credito'] ?? 0),
                    'dc'      => (int)($input['dias_credito'] ?? 0),
                    'pc'      => !empty($input['permite_credito']) ? 1 : 0,
                    'ar_iva'  => !empty($input['aplica_retencion_iva']) ? 1 : 0,
                    'pr_iva'  => (float)($input['porcentaje_retencion_iva'] ?? 75.0),
                    'ar_islr' => !empty($input['aplica_retencion_islr']) ? 1 : 0,
                    'est'     => isset($input['estado']) ? (int)$input['estado'] : 1
                ]);
                $mensaje = "Cliente '{$razon}' actualizado correctamente.";
            } else {
                $stmt = $db->prepare("
                    INSERT INTO clientes (
                        codigo, razon_social, nombre_comercial, documento_fiscal,
                        tipo_persona, tipo_contribuyente, direccion_fiscal, telefono,
                        email, zona_id, vendedor_id, lista_precio_default,
                        limite_credito, dias_credito, permite_credito,
                        aplica_retencion_iva, porcentaje_retencion_iva,
                        aplica_retencion_islr, estado
                    ) VALUES (
                        :cod, :raz, :nom_c, :doc,
                        :tp, :tc, :dir, :tel,
                        :em, :zon, :vend, :lp,
                        :lc, :dc, :pc,
                        :ar_iva, :pr_iva,
                        :ar_islr, :est
                    )
                ");
                $stmt->execute([
                    'cod'     => $codigo,
                    'raz'     => $razon,
                    'nom_c'   => $input['nombre_comercial'] ?? null,
                    'doc'     => $doc,
                    'tp'      => $input['tipo_persona'] ?? 'juridica',
                    'tc'      => $input['tipo_contribuyente'] ?? 'ordinario',
                    'dir'     => $input['direccion_fiscal'] ?? 'N/A',
                    'tel'     => $input['telefono'] ?? null,
                    'em'      => $input['email'] ?? null,
                    'zon'     => !empty($input['zona_id']) ? (int)$input['zona_id'] : 1,
                    'vend'    => !empty($input['vendedor_id']) ? (int)$input['vendedor_id'] : null,
                    'lp'      => $input['lista_precio_default'] ?? 'A',
                    'lc'      => (float)($input['limite_credito'] ?? 0),
                    'dc'      => (int)($input['dias_credito'] ?? 0),
                    'pc'      => !empty($input['permite_credito']) ? 1 : 0,
                    'ar_iva'  => !empty($input['aplica_retencion_iva']) ? 1 : 0,
                    'pr_iva'  => (float)($input['porcentaje_retencion_iva'] ?? 75.0),
                    'ar_islr' => !empty($input['aplica_retencion_islr']) ? 1 : 0,
                    'est'     => isset($input['estado']) ? (int)$input['estado'] : 1
                ]);
                $id = (int)$db->lastInsertId();
                $mensaje = "Cliente '{$razon}' registrado con éxito.";
            }

            echo json_encode(['status' => 'success', 'mensaje' => $mensaje, 'id' => $id]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // PROVEEDORES
    // =========================================================================
    public function getProveedores(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $q = trim($_GET['q'] ?? '');
            $sql = "SELECT * FROM proveedores";
            if ($q !== '') {
                $sql .= " WHERE razon_social LIKE :q OR codigo LIKE :q OR documento_fiscal LIKE :q";
            }
            $sql .= " ORDER BY razon_social ASC LIMIT 200";

            $stmt = $db->prepare($sql);
            if ($q !== '') {
                $stmt->execute(['q' => "%{$q}%"]);
            } else {
                $stmt->execute();
            }
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarProveedor(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $codigo = trim($input['codigo'] ?? 'PRV-' . rand(1000, 9999));
            $razon = trim($input['razon_social'] ?? '');
            $doc = trim($input['documento_fiscal'] ?? '');

            if ($razon === '' || $doc === '') {
                throw new Exception("La razón social y el documento fiscal (RIF/Cédula) del proveedor son obligatorios.");
            }

            if ($id) {
                $stmt = $db->prepare("
                    UPDATE proveedores SET
                        codigo = :cod, razon_social = :raz, documento_fiscal = :doc,
                        tipo_persona = :tp, tipo_contribuyente = :tc, direccion_fiscal = :dir,
                        telefono = :tel, email = :em, retencion_islr_concepto = :rislr_c,
                        retencion_islr_porcentaje = :rislr_p, estado = :est
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'      => $id,
                    'cod'     => $codigo,
                    'raz'     => $razon,
                    'doc'     => $doc,
                    'tp'      => $input['tipo_persona'] ?? 'juridica',
                    'tc'      => $input['tipo_contribuyente'] ?? 'ordinario',
                    'dir'     => $input['direccion_fiscal'] ?? 'N/A',
                    'tel'     => $input['telefono'] ?? null,
                    'em'      => $input['email'] ?? null,
                    'rislr_c' => $input['retencion_islr_concepto'] ?? null,
                    'rislr_p' => (float)($input['retencion_islr_porcentaje'] ?? 0),
                    'est'     => isset($input['estado']) ? (int)$input['estado'] : 1
                ]);
                $mensaje = "Proveedor '{$razon}' actualizado con éxito.";
            } else {
                $stmt = $db->prepare("
                    INSERT INTO proveedores (
                        codigo, razon_social, documento_fiscal, tipo_persona, tipo_contribuyente,
                        direccion_fiscal, telefono, email, retencion_islr_concepto,
                        retencion_islr_porcentaje, estado
                    ) VALUES (
                        :cod, :raz, :doc, :tp, :tc,
                        :dir, :tel, :em, :rislr_c,
                        :rislr_p, :est
                    )
                ");
                $stmt->execute([
                    'cod'     => $codigo,
                    'raz'     => $razon,
                    'doc'     => $doc,
                    'tp'      => $input['tipo_persona'] ?? 'juridica',
                    'tc'      => $input['tipo_contribuyente'] ?? 'ordinario',
                    'dir'     => $input['direccion_fiscal'] ?? 'N/A',
                    'tel'     => $input['telefono'] ?? null,
                    'em'      => $input['email'] ?? null,
                    'rislr_c' => $input['retencion_islr_concepto'] ?? null,
                    'rislr_p' => (float)($input['retencion_islr_porcentaje'] ?? 0),
                    'est'     => isset($input['estado']) ? (int)$input['estado'] : 1
                ]);
                $id = (int)$db->lastInsertId();
                $mensaje = "Proveedor '{$razon}' registrado exitosamente.";
            }

            echo json_encode(['status' => 'success', 'mensaje' => $mensaje, 'id' => $id]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // PRODUCTOS & INVENTARIO
    // =========================================================================
    public function getProductos(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $q = trim($_GET['q'] ?? '');
            $catId = !empty($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;

            $sql = "
                SELECT p.*, c.descripcion AS categoria_nombre, d.descripcion AS departamento_nombre,
                       d.id AS departamento_id, u.codigo AS unidad_codigo,
                       COALESCE((SELECT SUM(existencia) FROM inventario_existencias WHERE producto_id = p.id), 0) AS stock_total
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN departamentos d ON c.departamento_id = d.id
                LEFT JOIN unidades_medida u ON p.unidad_medida_id = u.id
                WHERE 1=1
            ";
            $params = [];
            if ($q !== '') {
                $sql .= " AND (p.descripcion LIKE :q1 OR p.codigo LIKE :q2 OR p.codigo_barra LIKE :q3)";
                $params['q1'] = "%{$q}%";
                $params['q2'] = "%{$q}%";
                $params['q3'] = "%{$q}%";
            }
            if ($catId) {
                $sql .= " AND p.categoria_id = :cat";
                $params['cat'] = $catId;
            }
            $sql .= " ORDER BY p.descripcion ASC LIMIT 200";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Cargar datos relacionales anidados para la Ficha Maestra
            foreach ($productos as &$p) {
                $pid = (int)$p['id'];
                // Presentaciones por SKU
                $stmtPres = $db->prepare("SELECT * FROM productos_presentaciones WHERE producto_id = :p AND estado = 1");
                $stmtPres->execute(['p' => $pid]);
                $p['presentaciones'] = $stmtPres->fetchAll(PDO::FETCH_ASSOC);

                // Depósitos y Stock por Almacén
                $stmtDep = $db->prepare("
                    SELECT d.id AS deposito_id, d.descripcion AS deposito_nombre, COALESCE(ie.existencia, 0) AS existencia,
                           COALESCE(ie.existencia_comprometida, 0) AS comprometida, COALESCE(ie.punto_reorden, 0) AS stock_minimo
                    FROM depositos d
                    LEFT JOIN inventario_existencias ie ON ie.deposito_id = d.id AND ie.producto_id = :p
                    WHERE d.estado = 1
                ");
                $stmtDep->execute(['p' => $pid]);
                $p['deposito_stock'] = $stmtDep->fetchAll(PDO::FETCH_ASSOC);

                // Lotes activos si aplica
                if (!empty($p['maneja_lotes'])) {
                    $stmtLotes = $db->prepare("SELECT * FROM producto_lotes WHERE producto_id = :p AND estado = 1 ORDER BY fecha_vencimiento ASC");
                    $stmtLotes->execute(['p' => $pid]);
                    $p['lotes'] = $stmtLotes->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $p['lotes'] = [];
                }

                // Seriales si aplica
                if (!empty($p['maneja_seriales'])) {
                    $stmtSer = $db->prepare("SELECT * FROM producto_seriales WHERE producto_id = :p AND estado = 'DISPONIBLE' LIMIT 50");
                    $stmtSer->execute(['p' => $pid]);
                    $p['seriales'] = $stmtSer->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $p['seriales'] = [];
                }
            }

            echo json_encode(['status' => 'success', 'data' => $productos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarProducto(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $codigo = trim($input['codigo'] ?? 'PRD-' . rand(1000, 9999));
            $desc = trim($input['descripcion'] ?? '');

            if ($desc === '') {
                throw new Exception("La descripción del producto es obligatoria.");
            }

            $costoUltimo = (float)($input['costo_ultimo'] ?? 0);
            $utilA = (float)($input['utilidad_a'] ?? 30);
            $precioA = !empty($input['precio_a']) ? (float)$input['precio_a'] : ($costoUltimo * (1 + $utilA / 100));

            $utilB = (float)($input['utilidad_b'] ?? 25);
            $precioB = !empty($input['precio_b']) ? (float)$input['precio_b'] : ($costoUltimo * (1 + $utilB / 100));

            $utilC = (float)($input['utilidad_c'] ?? 20);
            $precioC = !empty($input['precio_c']) ? (float)$input['precio_c'] : ($costoUltimo * (1 + $utilC / 100));

            $utilD = (float)($input['utilidad_d'] ?? 15);
            $precioD = !empty($input['precio_d']) ? (float)$input['precio_d'] : ($costoUltimo * (1 + $utilD / 100));

            Database::beginTransaction();

            if ($id) {
                $stmt = $db->prepare("
                    UPDATE productos SET
                        codigo = :cod, codigo_barra = :cb, descripcion = :desc,
                        categoria_id = :cat, unidad_medida_id = :um, tipo = :tp,
                        maneja_lotes = :ml, maneja_seriales = :ms, dias_garantia = :dgar, costo_ultimo = :cu,
                        costo_promedio = :cp, costo_reposicion = :cr,
                        utilidad_a = :ua, precio_a = :pa, utilidad_b = :ub, precio_b = :pb,
                        utilidad_c = :uc, precio_c = :pc, utilidad_d = :ud, precio_d = :pd,
                        porcentaje_iva = :piva, exento_iva = :ex, stock_minimo = :smin, stock_maximo = :smax,
                        publicar_en_ecommerce = :ecom, imagen_url = :img, estado = :est
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'     => $id,
                    'cod'    => $codigo,
                    'cb'     => $input['codigo_barra'] ?? null,
                    'desc'   => $desc,
                    'cat'    => !empty($input['categoria_id']) ? (int)$input['categoria_id'] : 1,
                    'um'     => !empty($input['unidad_medida_id']) ? (int)$input['unidad_medida_id'] : 1,
                    'tp'     => $input['tipo'] ?? 'producto',
                    'ml'     => !empty($input['maneja_lotes']) ? 1 : 0,
                    'ms'     => !empty($input['maneja_seriales']) ? 1 : 0,
                    'dgar'   => (int)($input['dias_garantia'] ?? 0),
                    'cu'     => $costoUltimo,
                    'cp'     => (float)($input['costo_promedio'] ?? $costoUltimo),
                    'cr'     => (float)($input['costo_reposicion'] ?? $costoUltimo),
                    'ua'     => $utilA,
                    'pa'     => $precioA,
                    'ub'     => $utilB,
                    'pb'     => $precioB,
                    'uc'     => $utilC,
                    'pc'     => $precioC,
                    'ud'     => $utilD,
                    'pd'     => $precioD,
                    'piva'   => (float)($input['porcentaje_iva'] ?? 16.0),
                    'ex'     => !empty($input['exento_iva']) ? 1 : 0,
                    'smin'   => (float)($input['stock_minimo'] ?? 0),
                    'smax'   => (float)($input['stock_maximo'] ?? 0),
                    'ecom'   => !empty($input['publicar_en_ecommerce']) ? 1 : 0,
                    'img'    => $input['imagen_url'] ?? null,
                    'est'    => isset($input['estado']) ? (int)$input['estado'] : 1
                ]);
                $mensaje = "Producto '{$desc}' actualizado con éxito.";
            } else {
                $stmt = $db->prepare("
                    INSERT INTO productos (
                        codigo, codigo_barra, descripcion, categoria_id, unidad_medida_id, tipo,
                        maneja_lotes, maneja_seriales, dias_garantia, costo_ultimo, costo_promedio, costo_reposicion,
                        utilidad_a, precio_a, utilidad_b, precio_b, utilidad_c, precio_c, utilidad_d, precio_d,
                        porcentaje_iva, exento_iva, stock_minimo, stock_maximo,
                        publicar_en_ecommerce, imagen_url, estado
                    ) VALUES (
                        :cod, :cb, :desc, :cat, :um, :tp,
                        :ml, :ms, :dgar, :cu, :cp, :cr,
                        :ua, :pa, :ub, :pb, :uc, :pc, :ud, :pd,
                        :piva, :ex, :smin, :smax,
                        :ecom, :img, :est
                    )
                ");
                $stmt->execute([
                    'cod'    => $codigo,
                    'cb'     => $input['codigo_barra'] ?? null,
                    'desc'   => $desc,
                    'cat'    => !empty($input['categoria_id']) ? (int)$input['categoria_id'] : 1,
                    'um'     => !empty($input['unidad_medida_id']) ? (int)$input['unidad_medida_id'] : 1,
                    'tp'     => $input['tipo'] ?? 'producto',
                    'ml'     => !empty($input['maneja_lotes']) ? 1 : 0,
                    'ms'     => !empty($input['maneja_seriales']) ? 1 : 0,
                    'dgar'   => (int)($input['dias_garantia'] ?? 0),
                    'cu'     => $costoUltimo,
                    'cp'     => (float)($input['costo_promedio'] ?? $costoUltimo),
                    'cr'     => (float)($input['costo_reposicion'] ?? $costoUltimo),
                    'ua'     => $utilA,
                    'pa'     => $precioA,
                    'ub'     => $utilB,
                    'pb'     => $precioB,
                    'uc'     => $utilC,
                    'pc'     => $precioC,
                    'ud'     => $utilD,
                    'pd'     => $precioD,
                    'piva'   => (float)($input['porcentaje_iva'] ?? 16.0),
                    'ex'     => !empty($input['exento_iva']) ? 1 : 0,
                    'smin'   => (float)($input['stock_minimo'] ?? 0),
                    'smax'   => (float)($input['stock_maximo'] ?? 0),
                    'ecom'   => !empty($input['publicar_en_ecommerce']) ? 1 : 0,
                    'img'    => $input['imagen_url'] ?? null,
                    'est'    => isset($input['estado']) ? (int)$input['estado'] : 1
                ]);
                $id = (int)$db->lastInsertId();

                // Inicializar existencia en depósito 1
                $db->prepare("
                    INSERT INTO inventario_existencias (producto_id, deposito_id, existencia)
                    VALUES (:p, 1, 0.0000)
                    ON DUPLICATE KEY UPDATE existencia = existencia
                ")->execute(['p' => $id]);

                $mensaje = "Producto '{$desc}' creado con éxito.";
            }

            // Sincronizar Múltiples Presentaciones (Sub-tabla dinámica)
            if (!empty($input['presentaciones']) && is_array($input['presentaciones'])) {
                // Marcar como inactivas previas si se actualiza
                $db->prepare("UPDATE productos_presentaciones SET estado = 0 WHERE producto_id = :p")->execute(['p' => $id]);
                $stmtPresIns = $db->prepare("
                    INSERT INTO productos_presentaciones (
                        producto_id, codigo_barra, nombre_presentacion, factor_conversion,
                        tipo_calculo_precio, porcentaje_recargo_fraccion, precio_a, precio_b, precio_c, precio_d, estado
                    ) VALUES (
                        :pid, :cb, :nom, :factor,
                        :calc, :recargo, :pa, :pb, :pc, :pd, 1
                    )
                    ON DUPLICATE KEY UPDATE
                        codigo_barra = VALUES(codigo_barra),
                        factor_conversion = VALUES(factor_conversion),
                        tipo_calculo_precio = VALUES(tipo_calculo_precio),
                        precio_a = VALUES(precio_a), precio_b = VALUES(precio_b),
                        precio_c = VALUES(precio_c), precio_d = VALUES(precio_d), estado = 1
                ");

                foreach ($input['presentaciones'] as $pres) {
                    if (empty(trim($pres['nombre_presentacion'] ?? ''))) continue;
                    $stmtPresIns->execute([
                        'pid'     => $id,
                        'cb'      => trim($pres['codigo_barra'] ?? ''),
                        'nom'     => trim($pres['nombre_presentacion']),
                        'factor'  => (float)($pres['factor_conversion'] ?? 1.0),
                        'calc'    => $pres['tipo_calculo_precio'] ?? 'PROPORCIONAL_DIRECTO',
                        'recargo' => (float)($pres['porcentaje_recargo_fraccion'] ?? 0.0),
                        'pa'      => (float)($pres['precio_a'] ?? 0.0),
                        'pb'      => (float)($pres['precio_b'] ?? 0.0),
                        'pc'      => (float)($pres['precio_c'] ?? 0.0),
                        'pd'      => (float)($pres['precio_d'] ?? 0.0)
                    ]);
                }
            }

            Database::commit();

            echo json_encode(['status' => 'success', 'mensaje' => $mensaje, 'id' => $id]);
        } catch (Exception $e) {
            Database::rollBack();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // DEPARTAMENTOS & CATEGORIAS
    // =========================================================================
    public function getDepartamentos(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $deptos = $db->query("SELECT * FROM departamentos ORDER BY descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);
            $cats = $db->query("SELECT c.*, d.descripcion AS depto_nombre FROM categorias c JOIN departamentos d ON c.departamento_id = d.id ORDER BY c.descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'departamentos' => $deptos,
                'categorias'    => $cats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarDepartamento(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $codigo = trim($input['codigo'] ?? 'DEP-' . rand(10, 99));
            $desc = trim($input['descripcion'] ?? '');

            if ($desc === '') throw new Exception("La descripción del departamento es obligatoria.");

            if ($id) {
                $stmt = $db->prepare("UPDATE departamentos SET codigo = :cod, descripcion = :desc, estado = :est WHERE id = :id");
                $stmt->execute(['id' => $id, 'cod' => $codigo, 'desc' => $desc, 'est' => $input['estado'] ?? 1]);
            } else {
                $stmt = $db->prepare("INSERT INTO departamentos (codigo, descripcion, estado) VALUES (:cod, :desc, 1)");
                $stmt->execute(['cod' => $codigo, 'desc' => $desc]);
            }

            echo json_encode(['status' => 'success', 'mensaje' => 'Departamento guardado.']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarCategoria(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $deptoId = (int)($input['departamento_id'] ?? 1);
            $codigo = trim($input['codigo'] ?? 'CAT-' . rand(10, 99));
            $desc = trim($input['descripcion'] ?? '');

            if ($desc === '') throw new Exception("La descripción de la categoría es requerida.");

            if ($id) {
                $stmt = $db->prepare("UPDATE categorias SET departamento_id = :did, codigo = :cod, descripcion = :desc, estado = :est WHERE id = :id");
                $stmt->execute(['id' => $id, 'did' => $deptoId, 'cod' => $codigo, 'desc' => $desc, 'est' => $input['estado'] ?? 1]);
            } else {
                $stmt = $db->prepare("INSERT INTO categorias (departamento_id, codigo, descripcion, estado) VALUES (:did, :cod, :desc, 1)");
                $stmt->execute(['did' => $deptoId, 'cod' => $codigo, 'desc' => $desc]);
            }

            echo json_encode(['status' => 'success', 'mensaje' => 'Categoría guardada.']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // VENDEDORES & COMISIONES
    // =========================================================================
    public function getVendedores(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $data = $db->query("SELECT * FROM vendedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarVendedor(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $codigo = trim($input['codigo'] ?? 'VEN-' . rand(10, 99));
            $nombre = trim($input['nombre'] ?? '');
            $cedula = trim($input['cedula_rif'] ?? '');

            if ($nombre === '' || $cedula === '') throw new Exception("Nombre y Cédula/RIF del vendedor son obligatorios.");

            if ($id) {
                $stmt = $db->prepare("
                    UPDATE vendedores SET
                        codigo = :cod, nombre = :nom, cedula_rif = :ced, telefono = :tel,
                        email = :em, comision_ventas = :cv, comision_cobros = :cc, estado = :est
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'  => $id,
                    'cod' => $codigo,
                    'nom' => $nombre,
                    'ced' => $cedula,
                    'tel' => $input['telefono'] ?? null,
                    'em'  => $input['email'] ?? null,
                    'cv'  => (float)($input['comision_ventas'] ?? 0),
                    'cc'  => (float)($input['comision_cobros'] ?? 0),
                    'est' => isset($input['estado']) ? (int)$input['estado'] : 1
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO vendedores (codigo, nombre, cedula_rif, telefono, email, comision_ventas, comision_cobros, estado)
                    VALUES (:cod, :nom, :ced, :tel, :em, :cv, :cc, 1)
                ");
                $stmt->execute([
                    'cod' => $codigo,
                    'nom' => $nombre,
                    'ced' => $cedula,
                    'tel' => $input['telefono'] ?? null,
                    'em'  => $input['email'] ?? null,
                    'cv'  => (float)($input['comision_ventas'] ?? 0),
                    'cc'  => (float)($input['comision_cobros'] ?? 0)
                ]);
            }

            echo json_encode(['status' => 'success', 'mensaje' => 'Vendedor guardado exitosamente.']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // ZONAS Y RUTAS
    // =========================================================================
    public function getZonas(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $data = $db->query("SELECT z.*, COUNT(c.id) AS total_clientes FROM zonas z LEFT JOIN clientes c ON c.zona_id = z.id GROUP BY z.id ORDER BY z.descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarZona(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $codigo = trim($input['codigo'] ?? 'ZON-' . rand(10, 99));
            $desc = trim($input['descripcion'] ?? '');

            if ($desc === '') throw new Exception("La descripción de la zona es requerida.");

            if ($id) {
                $stmt = $db->prepare("UPDATE zonas SET codigo = :cod, descripcion = :desc, estado = :est WHERE id = :id");
                $stmt->execute(['id' => $id, 'cod' => $codigo, 'desc' => $desc, 'est' => $input['estado'] ?? 1]);
            } else {
                $stmt = $db->prepare("INSERT INTO zonas (codigo, descripcion, estado) VALUES (:cod, :desc, 1)");
                $stmt->execute(['cod' => $codigo, 'desc' => $desc]);
            }

            echo json_encode(['status' => 'success', 'mensaje' => 'Zona guardada con éxito.']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // MONEDAS & TASAS DE CAMBIO
    // =========================================================================
    public function getMonedas(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $monedas = $db->query("
                SELECT m.*, 
                       (SELECT tasa FROM tasas_cambio WHERE moneda_id = m.id ORDER BY fecha DESC, id DESC LIMIT 1) AS tasa_actual,
                       (SELECT fecha FROM tasas_cambio WHERE moneda_id = m.id ORDER BY fecha DESC, id DESC LIMIT 1) AS fecha_tasa
                FROM monedas m
                ORDER BY m.es_base DESC, m.codigo ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $historico = $db->query("
                SELECT t.*, m.codigo AS moneda_codigo, m.nombre AS moneda_nombre
                FROM tasas_cambio t
                JOIN monedas m ON t.moneda_id = m.id
                ORDER BY t.fecha DESC, t.id DESC
                LIMIT 30
            ")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'monedas' => $monedas, 'historico' => $historico]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function actualizarTasa(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $monedaId = (int)($input['moneda_id'] ?? 2); // 2 = USD normalmente
            $tasa = (float)($input['tasa'] ?? 0);
            $fecha = !empty($input['fecha']) ? $input['fecha'] : date('Y-m-d');

            if ($tasa <= 0) throw new Exception("La tasa de cambio debe ser mayor a cero.");

            $stmt = $db->prepare("INSERT INTO tasas_cambio (moneda_id, tasa, fecha) VALUES (:m, :t, :f)");
            $stmt->execute(['m' => $monedaId, 't' => $tasa, 'f' => $fecha]);

            echo json_encode(['status' => 'success', 'mensaje' => "Tasa de cambio actualizada a {$tasa} al día {$fecha}."]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // DEPOSITOS / ALMACENES
    // =========================================================================
    public function getDepositos(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $data = $db->query("SELECT * FROM depositos ORDER BY descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarDeposito(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $codigo = trim($input['codigo'] ?? 'DEP-' . rand(10, 99));
            $desc = trim($input['descripcion'] ?? '');

            if ($desc === '') throw new Exception("La descripción del depósito es requerida.");

            if ($id) {
                $stmt = $db->prepare("UPDATE depositos SET codigo = :cod, descripcion = :desc, responsable = :resp, estado = :est WHERE id = :id");
                $stmt->execute(['id' => $id, 'cod' => $codigo, 'desc' => $desc, 'resp' => $input['responsable'] ?? null, 'est' => $input['estado'] ?? 1]);
            } else {
                $stmt = $db->prepare("INSERT INTO depositos (codigo, descripcion, responsable, estado) VALUES (:cod, :desc, :resp, 1)");
                $stmt->execute(['cod' => $codigo, 'desc' => $desc, 'resp' => $input['responsable'] ?? null]);
            }

            echo json_encode(['status' => 'success', 'mensaje' => 'Depósito guardado correctamente.']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // BANCOS Y CUENTAS DE TESORERIA
    // =========================================================================
    public function getBancos(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $data = $db->query("
                SELECT c.*, m.simbolo AS moneda_simbolo, m.codigo AS moneda_codigo
                FROM cuentas_bancarias c
                JOIN monedas m ON c.moneda_id = m.id
                ORDER BY c.tipo ASC, c.nombre_banco ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    public function guardarBanco(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = Database::getConnection();
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = !empty($input['id']) ? (int)$input['id'] : null;
            $tipo = $input['tipo'] ?? 'BANCO';
            $codigo = trim($input['codigo'] ?? 'CTA-' . rand(10, 99));
            $nombre = trim($input['nombre_banco'] ?? '');
            $numCta = trim($input['numero_cuenta'] ?? '');
            $monedaId = (int)($input['moneda_id'] ?? 1);
            $saldo = (float)($input['saldo_actual'] ?? 0);

            if ($nombre === '') throw new Exception("El nombre del banco o caja es obligatorio.");

            if ($id) {
                $stmt = $db->prepare("
                    UPDATE cuentas_bancarias SET
                        tipo = :tp, codigo = :cod, nombre_banco = :nom, numero_cuenta = :num,
                        moneda_id = :mid, saldo_actual = :sal, estado = :est
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'  => $id,
                    'tp'  => $tipo,
                    'cod' => $codigo,
                    'nom' => $nombre,
                    'num' => $numCta ?: null,
                    'mid' => $monedaId,
                    'sal' => $saldo,
                    'est' => isset($input['estado']) ? (int)$input['estado'] : 1
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO cuentas_bancarias (tipo, codigo, nombre_banco, numero_cuenta, moneda_id, saldo_actual, saldo_conciliado, estado)
                    VALUES (:tp, :cod, :nom, :num, :mid, :sal, :sal, 1)
                ");
                $stmt->execute([
                    'tp'  => $tipo,
                    'cod' => $codigo,
                    'nom' => $nombre,
                    'num' => $numCta ?: null,
                    'mid' => $monedaId,
                    'sal' => $saldo
                ]);
            }

            echo json_encode(['status' => 'success', 'mensaje' => 'Cuenta/Caja guardada con éxito.']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }
}
