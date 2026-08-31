<?php
namespace App\Models;
use App\Core\Database;
use Exception;
class PresentacionService {
   /**
    * Calcula los precios de venta de una presentación (Unidad, Caja, Bulto)
    */
   public static function calcularPreciosPresentacion(array $producto, array $presentacion): array {
       $factor = (float)$presentacion['factor_conversion'];
       if ($factor <= 0) $factor = 1.0;
       $tipoCalculo = $presentacion['tipo_calculo_precio'];
       $recargoFraccion = (float)($presentacion['porcentaje_recargo_fraccion'] ?? 0.0);
       $precios = [];
       $listas = ['a', 'b', 'c', 'd'];
       foreach ($listas as $lista) {
           $precioBaseProd = (float)$producto["precio_{$lista}"]; // Precio de la unidad base en el producto
           if ($presentacion['es_unidad_base']) {
               $precios["precio_{$lista}"] = $precioBaseProd;
               continue;
           }
           switch ($tipoCalculo) {
               case 'MANUAL':
                   $precios["precio_{$lista}"] = (float)$presentacion["precio_{$lista}"];
                   break;
               case 'PROPORCIONAL_DIRECTO':
                   // Ejemplo: Si el precio base es por unidad, la caja es Precio Base * Factor
                   $precios["precio_{$lista}"] = round($precioBaseProd * $factor, 4);
                   break;
               case 'MARGEN_FRACCION':
                   // Ejemplo: La caja tiene descuento por volumen o la unidad tiene recargo
                   $precioCalculado = ($precioBaseProd * $factor) * (1 + ($recargoFraccion / 100));
                   $precios["precio_{$lista}"] = round($precioCalculado, 4);
                   break;
           }
       }
       return $precios;
   }

   /**
    * Resuelve un producto y su presentación por código de barra (detecta si es barra de caja o unidad)
    */
   public static function buscarPorCodigoBarra(string $codigoBarra): ?array {
       $db = Database::getConnection();
       // 1. Buscar si el código de barra pertenece a una presentación específica (ej: código de la caja)
       $stmt = $db->prepare("
           SELECT 
               p.*,
               pp.id AS presentacion_id,
               pp.nombre_presentacion,
               pp.factor_conversion,
               pp.tipo_calculo_precio,
               pp.porcentaje_recargo_fraccion,
               pp.es_unidad_base
           FROM productos_presentaciones pp
           INNER JOIN productos p ON pp.producto_id = p.id
           WHERE pp.codigo_barra = :cb AND pp.estado = 1 AND p.estado = 1
           LIMIT 1
       ");
       $stmt->execute(['cb' => $codigoBarra]);
       $res = $stmt->fetch();
       if ($res) {
           $preciosCalc = self::calcularPreciosPresentacion($res, $res);
           return array_merge($res, $preciosCalc);
       }
       // 2. Si no, buscar por el código de barra principal del producto (unidad base)
       $stmtProd = $db->prepare("SELECT * FROM productos WHERE (codigo_barra = :cb OR codigo = :cod) AND estado = 1 LIMIT 1");
       $stmtProd->execute(['cb' => $codigoBarra, 'cod' => $codigoBarra]);
       $prod = $stmtProd->fetch();
       if ($prod) {
           return [
               'id'                  => $prod['id'],
               'codigo'              => $prod['codigo'],
               'descripcion'         => $prod['descripcion'],
               'presentacion_id'     => null,
               'nombre_presentacion' => 'Unidad Base',
               'factor_conversion'   => 1.0,
               'es_unidad_base'      => 1,
               'precio_a'            => $prod['precio_a'],
               'precio_b'            => $prod['precio_b'],
               'precio_c'            => $prod['precio_c'],
               'precio_d'            => $prod['precio_d'],
               'porcentaje_iva'      => $prod['porcentaje_iva'],
               'exento_iva'          => $prod['exento_iva']
           ];
       }
       return null;
   }
}