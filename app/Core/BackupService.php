<?php
namespace App\Core;
use App\Core\Database;
use PDO;
use Exception;
class BackupService {
   private const BACKUP_DIR = __DIR__ . '/../../storage/backups/';
   /**
    * Genera un volcado completo de la base de datos comprimido en .sql.gz
    */
   public static function generarRespaldo(string $tipo = 'MANUAL'): array {
       if (!is_dir(self::BACKUP_DIR)) {
           mkdir(self::BACKUP_DIR, 0755, true);
       }
       $db = Database::getConnection();
       $dbName = DB_NAME;
       $fecha = date('Ymd_His');
       $nombreArchivo = "backup_{$dbName}_{$fecha}.sql.gz";
       $rutaCompleta = self::BACKUP_DIR . $nombreArchivo;
       $gz = gzopen($rutaCompleta, 'w9');
       if (!$gz) {
           throw new Exception("No se pudo crear el archivo de respaldo en el almacenamiento local.");
       }
       // Encabezado del volcado SQL
       $header = "-- ========================================================\n"
               . "-- SISTEMA ADMINISTRATIVO - RESPALDO DE BASE DE DATOS\n"
               . "-- Licencia: " . LICENCIA_EMPRESA . " (RIF: " . LICENCIA_RIF . ")\n"
               . "-- Fecha: " . date('Y-m-d H:i:s') . "\n"
               . "-- ========================================================\n\n"
               . "SET FOREIGN_KEY_CHECKS=0;\n"
               . "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n"
               . "SET time_zone = \"+00:00\";\n\n";
       gzwrite($gz, $header);
       // Obtener listado de tablas
       $tablas = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
       $totalTablas = count($tablas);
       foreach ($tablas as $tabla) {
           // Ignorar cola de sincronización pesada temporal en el dump si se desea
           if ($tabla === 'sync_queue') continue;
           // 1. Estructura DDL
           $ddl = $db->query("SHOW CREATE TABLE `{$tabla}`")->fetch(PDO::FETCH_ASSOC);
           $tableDdl = "DROP TABLE IF EXISTS `{$tabla}`;\n" . $ddl['Create Table'] . ";\n\n";
           gzwrite($gz, $tableDdl);
           // 2. Datos en bloques de 500 registros
           $stmtCount = $db->query("SELECT COUNT(*) FROM `{$tabla}`");
           $totalRows = (int)$stmtCount->fetchColumn();
           if ($totalRows > 0) {
               $batchSize = 500;
               for ($offset = 0; $offset < $totalRows; $offset += $batchSize) {
                   $stmtData = $db->query("SELECT * FROM `{$tabla}` LIMIT {$offset}, {$batchSize}");
                   $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);
                   if (!empty($rows)) {
                       $columnas = array_keys($rows[0]);
                       $colsSql = implode("`, `", $columnas);
                       $insertSql = "INSERT INTO `{$tabla}` (`{$colsSql}`) VALUES \n";
                       $valoresFilas = [];
                       foreach ($rows as $row) {
                           $escaped = array_map(function ($val) use ($db) {
                               if ($val === null) return "NULL";
                               return $db->quote($val);
                           }, $row);

                           $valoresFilas[] = "(" . implode(", ", $escaped) . ")";
                       }
                       $insertSql .= implode(",\n", $valoresFilas) . ";\n\n";
                       gzwrite($gz, $insertSql);
                   }
               }
           }
       }
       gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
       gzclose($gz);
       $tamanoBytes = filesize($rutaCompleta);
       $sha256 = hash_file('sha256', $rutaCompleta);
       // Registrar en tabla de auditoría
       $stmtReg = $db->prepare("
           INSERT INTO respaldos_sistema (nombre_archivo, tamano_bytes, total_tablas, tipo, checksum_sha256)
           VALUES (:nom, :tam, :tab, :tipo, :hash)
       ");
       $stmtReg->execute([
           'nom'  => $nombreArchivo,
           'tam'  => $tamanoBytes,
           'tab'  => $totalTablas,
           'tipo' => $tipo,
           'hash' => $sha256
       ]);
       return [
           'status'         => 'success',
           'nombre_archivo' => $nombreArchivo,
           'tamano'         => round($tamanoBytes / (1024 * 1024), 2) . ' MB',
           'tablas'         => $totalTablas,
           'sha256'         => $sha256
       ];
   }
   /**
    * Restaura un respaldo .sql.gz en la base de datos local
    */
   public static function restaurarRespaldo(string $nombreArchivo): array {
       $rutaCompleta = self::BACKUP_DIR . basename($nombreArchivo);
       if (!file_exists($rutaCompleta)) {
           throw new Exception("El archivo de respaldo no existe.");
       }
       $gz = gzopen($rutaCompleta, 'r');
       if (!$gz) {
           throw new Exception("No se pudo descomprimir el archivo.");
       }
       $db = Database::getConnection();
       $sqlBuffer = '';
       $db->exec("SET FOREIGN_KEY_CHECKS=0;");
       while (!gzeof($gz)) {
           $linea = gzgets($gz, 4096);
           
           // Saltar comentarios y líneas vacías
           if (str_starts_with(trim($linea), '--') || trim($linea) === '') {
               continue;
           }
           $sqlBuffer .= $linea;
           if (str_ends_with(trim($linea), ';')) {
               $db->exec($sqlBuffer);
               $sqlBuffer = '';
           }
       }
       gzclose($gz);

       $db->exec("SET FOREIGN_KEY_CHECKS=1;");
       return [
           'status'  => 'success',
           'mensaje' => "Base de datos restaurada correctamente desde {$nombreArchivo}."
       ];
   }
}