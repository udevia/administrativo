<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Models\InventarioService;
use App\Models\DocumentoVentaService;
use Exception;
use PDO;

class SatService {
    /**
     * Crea o actualiza una orden de trabajo con sus campos dinámicos
     */
    public static function guardarOrden(array $cabecera, array $camposDinamicos = [], array $items = []): array {
        $db = Database::getConnection();
        try {
            Database::beginTransaction();
            $tipoServicioId = (int)($cabecera['tipo_servicio_id'] ?? 1);
            $ordenId = (int)($cabecera['id'] ?? 0);
            $numeroOrden = '';

            // 1. Generar correlativo si es nueva orden
            if (!$ordenId) {
                $stmtTipo = $db->prepare("SELECT prefijo_correlativo FROM sat_tipos_servicio WHERE id = :id");
                $stmtTipo->execute(['id' => $tipoServicioId]);
                $prefijo = $stmtTipo->fetchColumn() ?: 'OT-';
                $stmtCount = $db->query("SELECT COUNT(*) FROM sat_ordenes_trabajo");
                $secuencia = (int)$stmtCount->fetchColumn() + 1;
                $numeroOrden = $prefijo . str_pad((string)$secuencia, 6, '0', STR_PAD_LEFT);

                $stmtIns = $db->prepare("
                    INSERT INTO sat_ordenes_trabajo (
                        numero_orden, tipo_servicio_id, cliente_id, tecnico_responsable_id, estado_id,
                        fecha_recepcion, fecha_promesa_entrega, falla_reportada_cliente,
                        accesorios_recibidos, usuario_creador_id, moneda_id, tasa_cambio
                    ) VALUES (
                        :num, :tipo, :cli, :tec, 1,
                        NOW(), :f_prom, :falla,
                        :acc, :usr, 1, :tasa
                    )
                ");
                $stmtIns->execute([
                    'num'    => $numeroOrden,
                    'tipo'   => $tipoServicioId,
                    'cli'    => (int)$cabecera['cliente_id'],
                    'tec'    => !empty($cabecera['tecnico_responsable_id']) ? (int)$cabecera['tecnico_responsable_id'] : null,
                    'f_prom' => $cabecera['fecha_promesa_entrega'] ?? null,
                    'falla'  => trim($cabecera['falla_reportada_cliente'] ?? ''),
                    'acc'    => trim($cabecera['accesorios_recibidos'] ?? ''),
                    'usr'    => (int)($cabecera['usuario_id'] ?? 1),
                    'tasa'   => (float)($cabecera['tasa_cambio'] ?? Database::getTasaActualUsd())
                ]);
                $ordenId = (int)$db->lastInsertId();
            } else {
                $numeroOrden = $cabecera['numero_orden'] ?? '';
                $stmtUpd = $db->prepare("
                    UPDATE sat_ordenes_trabajo SET
                        tecnico_responsable_id = :tec,
                        estado_id = :est,
                        fecha_promesa_entrega = :f_prom,
                        diagnostico_tecnico = :diag,
                        trabajo_realizado = :trab,
                        accesorios_recibidos = :acc
                    WHERE id = :id
                ");
                $stmtUpd->execute([
                    'tec'    => !empty($cabecera['tecnico_responsable_id']) ? (int)$cabecera['tecnico_responsable_id'] : null,
                    'est'    => (int)($cabecera['estado_id'] ?? 1),
                    'f_prom' => $cabecera['fecha_promesa_entrega'] ?? null,
                    'diag'   => trim($cabecera['diagnostico_tecnico'] ?? ''),
                    'trab'   => trim($cabecera['trabajo_realizado'] ?? ''),
                    'acc'    => trim($cabecera['accesorios_recibidos'] ?? ''),
                    'id'     => $ordenId
                ]);
            }

            // 2. Guardar Metadatos / Campos Personalizados
            if (!empty($camposDinamicos)) {
                $stmtCampo = $db->prepare("
                    INSERT INTO sat_ordenes_valores_campos (orden_id, campo_id, valor)
                    VALUES (:oid, :cid, :val)
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                ");
                foreach ($camposDinamicos as $campoId => $valor) {
                    $stmtCampo->execute([
                        'oid' => $ordenId,
                        'cid' => (int)$campoId,
                        'val' => (string)$valor
                    ]);
                }
            }

            // 3. Guardar Mano de Obra y Repuestos si se suministran
            if (!empty($items)) {
                $db->prepare("DELETE FROM sat_ordenes_detalles WHERE orden_id = :id")->execute(['id' => $ordenId]);

                $stmtDet = $db->prepare("
                    INSERT INTO sat_ordenes_detalles (
                        orden_id, tipo_renglon, producto_id, deposito_id, descripcion,
                        cantidad, costo_unitario_historico, precio_unitario, subtotal, monto_iva, total, tecnico_comision_id
                    ) VALUES (
                        :oid, :tipo, :pid, :dep, :desc,
                        :cant, :costo, :precio, :sub, :iva, :tot, :tec
                    )
                ");
                $totalMO = 0.0;
                $totalRep = 0.0;
                foreach ($items as $item) {
                    $cant   = (float)$item['cantidad'];
                    $precio = (float)$item['precio_unitario'];
                    $sub    = $cant * $precio;
                    $iva    = (float)($item['monto_iva'] ?? 0.0);
                    $tot    = $sub + $iva;
                    if ($item['tipo_renglon'] === 'MANO_OBRA_SERVICIO') {
                        $totalMO += $sub;
                    } else {
                        $totalRep += $sub;
                    }
                    $stmtDet->execute([
                        'oid'    => $ordenId,
                        'tipo'   => $item['tipo_renglon'],
                        'pid'    => !empty($item['producto_id']) ? (int)$item['producto_id'] : null,
                        'dep'    => (int)($item['deposito_id'] ?? 1),
                        'desc'   => trim($item['descripcion']),
                        'cant'   => $cant,
                        'costo'  => (float)($item['costo_unitario'] ?? 0.0),
                        'precio' => $precio,
                        'sub'    => $sub,
                        'iva'    => $iva,
                        'tot'    => $tot,
                        'tec'    => !empty($item['tecnico_comision_id']) ? (int)$item['tecnico_comision_id'] : null
                    ]);
                }
                $totalGeneral = $totalMO + $totalRep;
                $db->prepare("
                    UPDATE sat_ordenes_trabajo 
                    SET total_mano_obra = :mo, total_repuestos = :rep, total_general = :tot 
                    WHERE id = :id
                ")->execute([
                    'mo'  => $totalMO,
                    'rep' => $totalRep,
                    'tot' => $totalGeneral,
                    'id'  => $ordenId
                ]);
            }

            Database::commit();
            return [
                'status'       => 'success',
                'orden_id'     => $ordenId,
                'numero_orden' => $numeroOrden,
                'mensaje'      => 'Orden de trabajo y recepción guardada correctamente.'
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Convierte la Orden de Trabajo terminada en Factura Fiscal o Nota de Entrega en el POS
     */
    public static function facturarOrden(int $ordenId, string $tipoDocumento = 'FACTURA', int $usuarioId = 1): array {
        $db = Database::getConnection();
        try {
            Database::beginTransaction();
            $stmt = $db->prepare("SELECT * FROM sat_ordenes_trabajo WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $ordenId]);
            $ot = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ot) throw new Exception("Orden de trabajo no encontrada.");
            if (!empty($ot['venta_id'])) throw new Exception("Esta orden ya fue facturada previamente.");

            $stmtDet = $db->prepare("SELECT * FROM sat_ordenes_detalles WHERE orden_id = :id");
            $stmtDet->execute(['id' => $ordenId]);
            $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
            if (empty($detalles)) {
                throw new Exception("La orden no tiene ítems de mano de obra o repuestos para facturar.");
            }

            // 1. Preparar ítems para el DocumentoVentaService
            $itemsVenta = [];
            foreach ($detalles as $d) {
                $itemsVenta[] = [
                    'producto_id'     => $d['producto_id'], // Puede ser NULL si es mano de obra pura
                    'descripcion'     => $d['descripcion'],
                    'cantidad'        => (float)$d['cantidad'],
                    'precio_unitario' => (float)$d['precio_unitario'],
                    'deposito_id'     => (int)$d['deposito_id']
                ];
            }

            // 2. Cabecera del documento de venta
            $cabeceraVenta = [
                'tipo_documento'   => $tipoDocumento,
                'numero_documento' => 'FAC-SAT-' . date('ymd') . '-' . rand(100, 999),
                'cliente_id'       => (int)$ot['cliente_id'],
                'vendedor_id'      => $ot['tecnico_responsable_id'] ?: $usuarioId,
                'deposito_id'      => 1,
                'usuario_id'       => $usuarioId,
                'fecha_emision'    => date('Y-m-d'),
                'fecha_vencimiento'=> date('Y-m-d'),
                'condicion_pago'   => 'CONTADO',
                'moneda_id'        => (int)$ot['moneda_id'],
                'tasa_cambio'      => (float)$ot['tasa_cambio'],
                'nota'             => "Liquidación de Servicio Orden N° {$ot['numero_orden']}"
            ];

            $resVenta = DocumentoVentaService::procesar($cabeceraVenta, $itemsVenta);
            $ventaId = (int)$resVenta['venta_id'];

            // 3. Actualizar la Orden de Trabajo a estado ENTREGADO / FACTURADO
            $stmtCierre = $db->prepare("
                UPDATE sat_ordenes_trabajo 
                SET estado_id = 7, venta_id = :vid, fecha_cierre_real = NOW() 
                WHERE id = :id
            ");
            $stmtCierre->execute(['vid' => $ventaId, 'id' => $ordenId]);

            Database::commit();
            return [
                'status'           => 'success',
                'venta_id'         => $ventaId,
                'numero_documento' => $cabeceraVenta['numero_documento'],
                'mensaje'          => "Orden {$ot['numero_orden']} facturada con éxito. Inventario de repuestos descargado."
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
