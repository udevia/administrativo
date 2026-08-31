<?php
namespace App\Models;
use App\Core\Database;
use App\Models\InventarioService;
use Exception;
class ImportadorMasivoService {
   /**
    * Definición de columnas requeridas por entidad
    */
   public static function getEstructuraEntidad(string $entidad): array {
       $estructuras = [
           'almacenes' => [
               'headers' => ['codigo', 'descripcion', 'responsable'],
               'sample'  => ['DEP-01', 'Almacén Principal Central', 'Carlos Pérez']
           ],
           'categorias' => [
               'headers' => ['departamento_codigo', 'categoria_codigo', 'categoria_descripcion'],
               'sample'  => ['DEP-VIV', 'CAT-ENL', 'Enlatados y Conservas']
           ],
           'productos' => [
               'headers' => [
                   'codigo', 'codigo_barra', 'descripcion', 'categoria_codigo', 'unidad_medida',
                   'costo_unitario', 'precio_a', 'precio_b', 'precio_c', 'precio_d',
                   'porcentaje_iva', 'exento_iva', 'deposito_codigo', 'stock_inicial'
               ],
               'sample'  => [
                   'PROD-001', '7591234567890', 'Atún Desmenuzado en Aceite 140g', 'CAT-ENL', 'UND',
                   '1.2000', '1.8000', '1.7000', '1.6000', '1.5000',
                   '16.00', '0', 'DEP-01', '100.0000'
               ]
           ],
           'clientes' => [

               'headers' => [
                   'codigo', 'razon_social', 'documento_fiscal', 'tipo_persona', 'tipo_contribuyente',
                   'direccion_fiscal', 'telefono', 'email', 'limite_credito', 'dias_credito', 'lista_precio_default'
               ],
               'sample'  => [
                   'CLI-001', 'Comercial La Esperanza C.A.', 'J-12345678-9', 'juridica', 'ordinario',
                   'Av. Bolívar Local 10', '04141234567', 'contacto@esperanza.com', '500.00', '15', 'A'
               ]
           ],
           'proveedores' => [
                'headers' => [
                    'codigo', 'razon_social', 'documento_fiscal', 'direccion_fiscal', 'telefono', 'email', 'persona_contacto'
                ],
               'sample'  => [
                   'PROV-001', 'Distribuidora Polar C.A.', 'J-00001234-0', 'Zona Industrial II', '02418889900', 'ventas@polar.com'
               ]
           ]
       ];
       return $estructuras[$entidad] ?? throw new Exception("Entidad de importación '{$entidad}' no válida.");
   }
   /**
    * Genera la plantilla CSV compatible con Excel
    */
   public static function generarCsvPlantilla(string $entidad): string {
       $estructura = self::getEstructuraEntidad($entidad);
       $output = fopen('php://temp', 'r+');
       fputcsv($output, $estructura['headers'], ';');
       fputcsv($output, $estructura['sample'], ';');
       rewind($output);
       $csv = stream_get_contents($output);
       fclose($output);
       return $csv;
   }
   /**
    * Parsea y valida el archivo cargado para generar la vista previa
    */
   public static function analizarArchivo(string $entidad, string $tmpFilePath): array {
       $estructura = self::getEstructuraEntidad($entidad);
       $headersEsperados = $estructura['headers'];
       if (!file_exists($tmpFilePath) || !is_readable($tmpFilePath)) {
           throw new Exception("No se pudo leer el archivo cargado.");
       }
       $handle = fopen($tmpFilePath, 'r');
       $delimitador = self::detectarDelimitador($tmpFilePath);
       
       // Leer encabezados
       $headersArchivo = fgetcsv($handle, 0, $delimitador);
       if (!$headersArchivo) {
           throw new Exception("El archivo subido está vacío.");
       }
       // Sanitizar encabezados eliminando espacios y caracteres invisibles UTF-8 BOM
       $headersArchivo = array_map(function($h) {
           return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', strtolower($h)));
       }, $headersArchivo);
       $filas = [];
       $filaNumero = 1;
       $errores = [];
       while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
           $filaNumero++;
           if (empty(array_filter($data))) continue; // Saltar filas vacías
           if (count($data) !== count($headersEsperados)) {
               $errores[] = "Fila {$filaNumero}: El número de columnas no coincide con la plantilla (esperadas: " . count($headersEsperados) . ", recibidas: " . count($data) . ").";
               continue;
           }

           $filaAsociativa = array_combine($headersEsperados, $data);
           $filas[] = $filaAsociativa;
       }
       fclose($handle);
       return [
           'entidad'       => $entidad,
           'total_filas'   => count($filas),
           'headers'       => $headersEsperados,
           'vista_previa'  => array_slice($filas, 0, 10), // Primeras 10 filas para preview
           'filas_totales' => $filas,
           'errores'       => $errores,
           'valido'        => empty($errores)
       ];
   }
   /**
    * Procesa la inserción atómica de los datos validados
    */
   public static function procesarImportacion(string $entidad, array $filas, int $usuarioId = 1): array {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           $procesados = 0;
           switch ($entidad) {
               case 'almacenes':
                   $stmt = $db->prepare("
                       INSERT INTO depositos (codigo, descripcion, responsable, estado)
                       VALUES (:codigo, :descripcion, :responsable, 1)
                       ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), responsable = VALUES(responsable)
                   ");
                   foreach ($filas as $f) {
                       $stmt->execute([
                           'codigo'      => trim($f['codigo']),
                           'descripcion' => trim($f['descripcion']),
                           'responsable' => trim($f['responsable'] ?? 'Almacenista')
                       ]);
                       $procesados++;
                   }
                   break;
               case 'categorias':
                   foreach ($filas as $f) {
                       // 1. Resolver o crear departamento
                       $depCodigo = trim($f['departamento_codigo']);
                       $stmtDep = $db->prepare("SELECT id FROM departamentos WHERE codigo = :c");
                       $stmtDep->execute(['c' => $depCodigo]);
                       $depId = $stmtDep->fetchColumn();
                       if (!$depId) {
                           $stmtInsDep = $db->prepare("INSERT INTO departamentos (codigo, descripcion) VALUES (:c, :d)");
                           $stmtInsDep->execute(['c' => $depCodigo, 'd' => 'Depto ' . $depCodigo]);
                           $depId = (int)$db->lastInsertId();
                       }
                       // 2. Insertar Categoría
                       $stmtCat = $db->prepare("
                           INSERT INTO categorias (departamento_id, codigo, descripcion)
                           VALUES (:dep, :c, :d)
                           ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)
                       ");
                       $stmtCat->execute([
                           'dep' => $depId,
                           'c'   => trim($f['categoria_codigo']),
                           'd'   => trim($f['categoria_descripcion'])
                       ]);
                       $procesados++;
                   }
                   break;
               case 'productos':
                   foreach ($filas as $f) {

                       // Resolver Categoría
                       $catCodigo = trim($f['categoria_codigo']);
                       $stmtCat = $db->prepare("SELECT id FROM categorias WHERE codigo = :c");
                       $stmtCat->execute(['c' => $catCodigo]);
                       $catId = $stmtCat->fetchColumn();
                       if (!$catId) {
                           throw new Exception("La categoría '{$catCodigo}' no existe en el sistema. Impórtela previamente.");
                       }
                       // Resolver Unidad de Medida
                       $umCodigo = trim($f['unidad_medida'] ?? 'UND');
                       $stmtUm = $db->prepare("SELECT id FROM unidades_medida WHERE codigo = :c");
                       $stmtUm->execute(['c' => $umCodigo]);
                       $umId = $stmtUm->fetchColumn();
                       if (!$umId) {
                           $db->prepare("INSERT INTO unidades_medida (codigo, nombre, decimales) VALUES (:c, :n, 0)")
                              ->execute(['c' => $umCodigo, 'n' => $umCodigo]);
                           $umId = (int)$db->lastInsertId();
                       }
                       // Insertar o actualizar producto
                       $costo = (float)($f['costo_unitario'] ?? 0);
                       $stmtProd = $db->prepare("
                           INSERT INTO productos (
                               codigo, codigo_barra, descripcion, categoria_id, unidad_medida_id,
                               costo_ultimo, costo_promedio, costo_reposicion,
                               precio_a, precio_b, precio_c, precio_d, porcentaje_iva, exento_iva, estado
                           ) VALUES (
                               :codigo, :barra, :descripcion, :cat, :um,
                               :costo, :costo, :costo,
                               :pa, :pb, :pc, :pd, :iva, :exento, 1
                           )
                           ON DUPLICATE KEY UPDATE 
                               descripcion = VALUES(descripcion),
                               precio_a = VALUES(precio_a),
                               precio_b = VALUES(precio_b),
                               precio_c = VALUES(precio_c),
                               precio_d = VALUES(precio_d)
                       ");
                       $stmtProd->execute([
                           'codigo'      => trim($f['codigo']),
                           'barra'       => !empty($f['codigo_barra']) ? trim($f['codigo_barra']) : null,
                           'descripcion' => trim($f['descripcion']),
                           'cat'         => $catId,
                           'um'          => $umId,
                           'costo'       => $costo,
                           'pa'          => (float)($f['precio_a'] ?? 0),
                           'pb'          => (float)($f['precio_b'] ?? 0),
                           'pc'          => (float)($f['precio_c'] ?? 0),
                           'pd'          => (float)($f['precio_d'] ?? 0),
                           'iva'         => (float)($f['porcentaje_iva'] ?? 16),
                           'exento'      => (int)($f['exento_iva'] ?? 0)
                       ]);
                       $stmtGetProd = $db->prepare("SELECT id FROM productos WHERE codigo = :c");
                       $stmtGetProd->execute(['c' => trim($f['codigo'])]);
                       $productoId = (int)$stmtGetProd->fetchColumn();
                       // Cargar Stock Inicial si viene especificado
                       $stockInicial = (float)($f['stock_inicial'] ?? 0);
                       if ($stockInicial > 0 && !empty($f['deposito_codigo'])) {
                           $stmtDep = $db->prepare("SELECT id FROM depositos WHERE codigo = :c");
                           $stmtDep->execute(['c' => trim($f['deposito_codigo'])]);
                           $depositoId = $stmtDep->fetchColumn();
                           if ($depositoId) {
                               InventarioService::registrarMovimiento(
                                   $productoId,
                                   (int)$depositoId,
                                   'AJUSTE_POSITIVO',
                                   'INV_INICIAL',

                                   'MIGRACION-EXCEL',
                                   $stockInicial,
                                   $costo,
                                   $usuarioId,
                                   'Carga inicial de inventario vía migración masiva'
                               );
                           }
                       }
                       $procesados++;
                   }
                   break;
               case 'clientes':
                   $stmt = $db->prepare("
                       INSERT INTO clientes (
                           codigo, razon_social, documento_fiscal, tipo_persona, tipo_contribuyente,
                           direccion_fiscal, telefono, email, zona_id, vendedor_id,
                           limite_credito, dias_credito, permite_credito, lista_precio_default, estado
                       ) VALUES (
                           :codigo, :razon_social, :doc, :tipo_p, :tipo_c,
                           :dir, :tel, :email, 1, 1,
                           :limite, :dias, :permite_cred, :lista, 1
                       )
                       ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social), telefono = VALUES(telefono)
                   ");
                   foreach ($filas as $f) {
                       $limite = (float)($f['limite_credito'] ?? 0);
                       $stmt->execute([
                           'codigo'       => trim($f['codigo']),
                           'razon_social' => trim($f['razon_social']),
                           'doc'          => trim($f['documento_fiscal']),
                           'tipo_p'       => $f['tipo_persona'] ?? 'juridica',
                           'tipo_c'       => $f['tipo_contribuyente'] ?? 'ordinario',
                           'dir'          => trim($f['direccion_fiscal']),
                           'tel'          => trim($f['telefono'] ?? ''),
                           'email'        => trim($f['email'] ?? ''),
                           'limite'       => $limite,
                           'dias'         => (int)($f['dias_credito'] ?? 0),
                           'permite_cred' => ($limite > 0) ? 1 : 0,
                           'lista'        => strtoupper(trim($f['lista_precio_default'] ?? 'A'))
                       ]);
                       $procesados++;
                   }
                   break;
                case 'proveedores':
                    $stmt = $db->prepare("
                        INSERT INTO proveedores (
                            codigo, razon_social, documento_fiscal, direccion_fiscal, telefono, email, persona_contacto, dias_credito, estado
                        ) VALUES (
                            :codigo, :razon_social, :doc, :dir, :tel, :email, :contacto, :dias, 1
                        )
                        ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social), telefono = VALUES(telefono)
                    ");
                   foreach ($filas as $f) {
                       $stmt->execute([
                           'codigo'       => trim($f['codigo']),
                           'razon_social' => trim($f['razon_social']),
                           'doc'          => trim($f['documento_fiscal']),
                           'dir'          => trim($f['direccion_fiscal']),
                           'tel'          => trim($f['telefono'] ?? ''),
                           'email'        => trim($f['email'] ?? ''),
                           'contacto'     => trim($f['persona_contacto'] ?? ''),
                           'dias'         => (int)($f['dias_credito'] ?? 0)
                       ]);
                       $procesados++;
                   }
                   break;
           }
           Database::commit();
           return [

               'status'     => 'success',
               'procesados' => $procesados,
               'mensaje'    => "Se importaron exitosamente {$procesados} registros en {$entidad}."
           ];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
   private static function detectarDelimitador(string $csvFile): string {
       $delimitadores = [';', ',', '\t', '|'];
       $handle = fopen($csvFile, 'r');
       $primeraLinea = fgets($handle);
       fclose($handle);
       $conteoMax = 0;
       $delimitadorFinal = ';';
       foreach ($delimitadores as $d) {
           $conteo = substr_count($primeraLinea, $d);
           if ($conteo > $conteoMax) {
               $conteoMax = $conteo;
               $delimitadorFinal = $d;
           }
       }
       return $delimitadorFinal;
   }
}