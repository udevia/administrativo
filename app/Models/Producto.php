<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Producto {
    public static function all(?int $categoriaId = null, ?string $busqueda = null): array {
        $db = Database::getConnection();
        $sql = "
            SELECT 
                p.*,
                c.descripcion AS categoria_nombre,
                um.nombre AS unidad_medida_nombre,
                COALESCE(SUM(pd.existencia), 0) AS stock_total,
                COALESCE(SUM(pd.existencia_comprometida), 0) AS stock_comprometido,
                COALESCE(SUM(pd.existencia - pd.existencia_comprometida), 0) AS stock_disponible
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            INNER JOIN unidades_medida um ON p.unidad_medida_id = um.id
            LEFT JOIN producto_deposito pd ON p.id = pd.producto_id
            WHERE p.estado = 1
        ";

        $params = [];
        if ($categoriaId) {
            $sql .= " AND p.categoria_id = :cat";
            $params['cat'] = $categoriaId;
        }

        if (!empty($busqueda)) {
            $sql .= " AND (p.codigo LIKE :b1 OR p.codigo_barra LIKE :b2 OR p.descripcion LIKE :b3)";
            $params['b1'] = "%{$busqueda}%";
            $params['b2'] = "%{$busqueda}%";
            $params['b3'] = "%{$busqueda}%";
        }

        $sql .= " GROUP BY p.id ORDER BY p.descripcion ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                p.*,
                c.descripcion AS categoria_nombre,
                um.nombre AS unidad_medida_nombre,
                COALESCE(SUM(pd.existencia), 0) AS stock_total,
                COALESCE(SUM(pd.existencia_comprometida), 0) AS stock_comprometido,
                COALESCE(SUM(pd.existencia - pd.existencia_comprometida), 0) AS stock_disponible
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            INNER JOIN unidades_medida um ON p.unidad_medida_id = um.id
            LEFT JOIN producto_deposito pd ON p.id = pd.producto_id
            WHERE p.id = :id
            GROUP BY p.id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function findByCodigo(string $codigo): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                p.*,
                c.descripcion AS categoria_nombre,
                um.nombre AS unidad_medida_nombre,
                COALESCE(SUM(pd.existencia), 0) AS stock_total,
                COALESCE(SUM(pd.existencia_comprometida), 0) AS stock_comprometido,
                COALESCE(SUM(pd.existencia - pd.existencia_comprometida), 0) AS stock_disponible
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            INNER JOIN unidades_medida um ON p.unidad_medida_id = um.id
            LEFT JOIN producto_deposito pd ON p.id = pd.producto_id
            WHERE p.codigo = :cod OR p.codigo_barra = :cod
            GROUP BY p.id
            LIMIT 1
        ");
        $stmt->execute(['cod' => trim($codigo)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function obtenerStockPorDeposito(int $productoId, int $depositoId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT existencia, existencia_comprometida, existencia_por_llegar, punto_reorden,
                   (existencia - existencia_comprometida) AS disponible
            FROM producto_deposito 
            WHERE producto_id = :p AND deposito_id = :d
            LIMIT 1
        ");
        $stmt->execute(['p' => $productoId, 'd' => $depositoId]);
        $row = $stmt->fetch();

        return $row ?: [
            'existencia' => 0.0,
            'existencia_comprometida' => 0.0,
            'existencia_por_llegar' => 0.0,
            'punto_reorden' => 0.0,
            'disponible' => 0.0
        ];
    }

    public static function guardar(array $datos): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO productos (
                codigo, codigo_barra, descripcion, categoria_id, unidad_medida_id,
                tipo, maneja_lotes, maneja_seriales, costo_ultimo, costo_promedio, costo_reposicion,
                utilidad_a, precio_a, utilidad_b, precio_b, utilidad_c, precio_c, utilidad_d, precio_d,
                porcentaje_iva, exento_iva, stock_minimo, stock_maximo, estado
            ) VALUES (
                :codigo, :codigo_barra, :descripcion, :categoria_id, :unidad_medida_id,
                :tipo, :maneja_lotes, :maneja_seriales, :costo_ultimo, :costo_promedio, :costo_reposicion,
                :utilidad_a, :precio_a, :utilidad_b, :precio_b, :utilidad_c, :precio_c, :utilidad_d, :precio_d,
                :porcentaje_iva, :exento_iva, :stock_minimo, :stock_maximo, 1
            )
        ");

        $stmt->execute([
            'codigo'            => trim((string)$datos['codigo']),
            'codigo_barra'      => !empty($datos['codigo_barra']) ? trim((string)$datos['codigo_barra']) : null,
            'descripcion'       => trim((string)$datos['descripcion']),
            'categoria_id'      => (int)$datos['categoria_id'],
            'unidad_medida_id'  => (int)($datos['unidad_medida_id'] ?? 1),
            'tipo'              => $datos['tipo'] ?? 'producto',
            'maneja_lotes'      => (int)($datos['maneja_lotes'] ?? 0),
            'maneja_seriales'   => (int)($datos['maneja_seriales'] ?? 0),
            'costo_ultimo'      => (float)($datos['costo_ultimo'] ?? 0.0),
            'costo_promedio'    => (float)($datos['costo_promedio'] ?? 0.0),
            'costo_reposicion'  => (float)($datos['costo_reposicion'] ?? 0.0),
            'utilidad_a'        => (float)($datos['utilidad_a'] ?? 30.0),
            'precio_a'          => (float)($datos['precio_a'] ?? 0.0),
            'utilidad_b'        => (float)($datos['utilidad_b'] ?? 25.0),
            'precio_b'          => (float)($datos['precio_b'] ?? 0.0),
            'utilidad_c'        => (float)($datos['utilidad_c'] ?? 20.0),
            'precio_c'          => (float)($datos['precio_c'] ?? 0.0),
            'utilidad_d'        => (float)($datos['utilidad_d'] ?? 15.0),
            'precio_d'          => (float)($datos['precio_d'] ?? 0.0),
            'porcentaje_iva'    => (float)($datos['porcentaje_iva'] ?? 16.0),
            'exento_iva'        => (int)($datos['exento_iva'] ?? 0),
            'stock_minimo'      => (float)($datos['stock_minimo'] ?? 0.0),
            'stock_maximo'      => (float)($datos['stock_maximo'] ?? 0.0)
        ]);

        return (int)$db->lastInsertId();
    }
}
