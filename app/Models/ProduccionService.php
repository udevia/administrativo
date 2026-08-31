<?php
namespace App\Models;

use App\Core\Database;
use Exception;

class ProduccionService {

    public static function listarFormulas() {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT f.*, p.descripcion as producto_terminado_nombre, p.codigo as producto_terminado_codigo,
                   (SELECT COUNT(*) FROM produccion_formulas_detalles fd WHERE fd.formula_id = f.id) as num_materiales
            FROM produccion_formulas f
            JOIN productos p ON f.producto_terminado_id = p.id
            ORDER BY f.nombre_formula
        ");
        $formulas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($formulas as &$f) {
            $stmtDet = $db->prepare("
                SELECT fd.*, p.descripcion as material_nombre 
                FROM produccion_formulas_detalles fd
                JOIN productos p ON fd.material_id = p.id
                WHERE fd.formula_id = :id
            ");
            $stmtDet->execute(['id' => $f['id']]);
            $f['materiales'] = $stmtDet->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $formulas;
    }

    public static function guardarFormula(array $data) {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            if (empty($data['nombre_formula']) || empty($data['producto_terminado_id']) || empty($data['materiales'])) {
                throw new Exception("Faltan datos obligatorios para la fórmula.");
            }

            // Calcular costo
            $costoTotal = 0.0;
            foreach ($data['materiales'] as $mat) {
                $costoTotal += (float)($mat['costo_estimado'] ?? 0);
            }

            $stmt = $db->prepare("
                INSERT INTO produccion_formulas (nombre_formula, producto_terminado_id, rendimiento_lote, costo_estimado)
                VALUES (:nom, :prod, :rend, :costo)
            ");
            $stmt->execute([
                'nom'   => $data['nombre_formula'],
                'prod'  => (int)$data['producto_terminado_id'],
                'rend'  => (float)($data['rendimiento_lote'] ?? 1),
                'costo' => $costoTotal
            ]);
            $formulaId = (int)$db->lastInsertId();

            $stmtDet = $db->prepare("
                INSERT INTO produccion_formulas_detalles (formula_id, material_id, cantidad_requerida, unidad, costo_estimado)
                VALUES (:fid, :mid, :cant, :uni, :costo)
            ");
            foreach ($data['materiales'] as $mat) {
                $stmtDet->execute([
                    'fid'   => $formulaId,
                    'mid'   => (int)$mat['material_id'],
                    'cant'  => (float)$mat['cantidad_requerida'],
                    'uni'   => $mat['unidad'] ?? 'Unid',
                    'costo' => (float)($mat['costo_estimado'] ?? 0)
                ]);
            }

            $db->commit();
            return $formulaId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function listarOrdenes() {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT o.*, f.nombre_formula
            FROM produccion_ordenes o
            LEFT JOIN produccion_formulas f ON o.formula_id = f.id
            ORDER BY o.id DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function iniciarOrden(array $data) {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $formulaId = (int)$data['formula_id'];
            $cantPlan  = (float)($data['cantidad_planificada'] ?? 1);
            $depositoId = (int)($data['deposito_id'] ?? 1);
            $userId    = (int)($data['usuario_id'] ?? 1);

            // Generar número de OP
            $stmt = $db->query("SELECT COALESCE(MAX(id), 0) + 1 AS siguiente FROM produccion_ordenes");
            $num = $stmt->fetch(\PDO::FETCH_ASSOC)['siguiente'] ?? 1;
            $numOrden = 'OP-' . date('ym') . str_pad($num, 4, '0', STR_PAD_LEFT);

            // Obtener formula
            $stmtF = $db->prepare("SELECT * FROM produccion_formulas WHERE id = :id");
            $stmtF->execute(['id' => $formulaId]);
            $formula = $stmtF->fetch(\PDO::FETCH_ASSOC);
            if (!$formula) throw new Exception("Fórmula no encontrada.");

            // Obtener materiales
            $stmtM = $db->prepare("SELECT * FROM produccion_formulas_detalles WHERE formula_id = :id");
            $stmtM->execute(['id' => $formulaId]);
            $materiales = $stmtM->fetchAll(\PDO::FETCH_ASSOC);

            // Insertar OP
            $stmtOP = $db->prepare("
                INSERT INTO produccion_ordenes (
                    numero_orden, formula_id, cantidad_planificada, deposito_id, estado, usuario_id
                ) VALUES (
                    :num, :fid, :cant, :dep, 'EN_PROCESO', :uid
                )
            ");
            $stmtOP->execute([
                'num'  => $numOrden,
                'fid'  => $formulaId,
                'cant' => $cantPlan,
                'dep'  => $depositoId,
                'uid'  => $userId
            ]);
            $ordenId = (int)$db->lastInsertId();

            // Insertar consumos planificados
            $stmtConsumo = $db->prepare("
                INSERT INTO produccion_ordenes_consumo (orden_id, material_id, cantidad_consumida, costo_unitario)
                VALUES (:oid, :mid, :cant, :costo)
            ");
            
            // Consumo y descuento de inventario
            foreach ($materiales as $mat) {
                // Cálculo de consumo: si la fórmula rinde X, para producir Y consumimos (Y / X) * cant_requerida_lote
                $rendimiento = (float)($formula['rendimiento_lote'] ?: 1);
                $cantConsumir = ($cantPlan / $rendimiento) * (float)$mat['cantidad_requerida'];
                $costoUnitario = ((float)$mat['costo_estimado'] / (float)$mat['cantidad_requerida']); // Costo por unidad de materia prima

                $stmtConsumo->execute([
                    'oid'   => $ordenId,
                    'mid'   => $mat['material_id'],
                    'cant'  => round($cantConsumir, 4),
                    'costo' => round($costoUnitario, 2)
                ]);

                // Descontar materia prima del inventario (Opcional, en 'EN_PROCESO' podría ser reservado, aquí lo descontamos directo)
                $db->prepare("
                    INSERT INTO kardex (producto_id, tipo_movimiento, cantidad, deposito_id, documento_referencia)
                    VALUES (?, 'SALIDA', ?, ?, ?)
                ")->execute([
                    $mat['material_id'],
                    $cantConsumir,
                    $depositoId,
                    $numOrden
                ]);
            }

            $db->commit();
            return ['orden_id' => $ordenId, 'numero_orden' => $numOrden];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function completarOrden(int $ordenId, float $cantidadReal) {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmtO = $db->prepare("SELECT * FROM produccion_ordenes WHERE id = :id AND estado != 'COMPLETADA'");
            $stmtO->execute(['id' => $ordenId]);
            $orden = $stmtO->fetch(\PDO::FETCH_ASSOC);
            if (!$orden) throw new Exception("Orden no válida o ya completada.");

            $stmtF = $db->prepare("SELECT * FROM produccion_formulas WHERE id = :id");
            $stmtF->execute(['id' => $orden['formula_id']]);
            $formula = $stmtF->fetch(\PDO::FETCH_ASSOC);
            if (!$formula) throw new Exception("Fórmula no asociada.");

            // Calcular costo total
            $stmtC = $db->prepare("SELECT SUM(cantidad_consumida * costo_unitario) as total_mp FROM produccion_ordenes_consumo WHERE orden_id = :id");
            $stmtC->execute(['id' => $ordenId]);
            $costoTotalMp = (float)($stmtC->fetchColumn() ?: 0);

            $costoUnitarioTerminado = $cantidadReal > 0 ? ($costoTotalMp / $cantidadReal) : 0;

            // Actualizar Orden
            $stmtUpd = $db->prepare("
                UPDATE produccion_ordenes 
                SET estado = 'COMPLETADA', cantidad_fabricada_real = :cant, 
                    costo_total_materia_prima = :ctotal, costo_unitario_terminado = :cunit
                WHERE id = :id
            ");
            $stmtUpd->execute([
                'cant'   => $cantidadReal,
                'ctotal' => $costoTotalMp,
                'cunit'  => $costoUnitarioTerminado,
                'id'     => $ordenId
            ]);

            // Ingresar Producto Terminado al Inventario
            $db->prepare("
                INSERT INTO kardex (producto_id, tipo_movimiento, cantidad, costo_unitario, deposito_id, documento_referencia)
                VALUES (?, 'ENTRADA', ?, ?, ?, ?)
            ")->execute([
                $formula['producto_terminado_id'],
                $cantidadReal,
                $costoUnitarioTerminado,
                $orden['deposito_id'] ?: 1,
                $orden['numero_orden']
            ]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}