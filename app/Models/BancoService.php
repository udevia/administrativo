<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class BancoService {
    public static function obtenerCuentas(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT cb.*, m.codigo AS moneda_codigo, m.simbolo AS moneda_simbolo
            FROM cuentas_bancarias cb
            INNER JOIN monedas m ON cb.moneda_id = m.id
            WHERE cb.estado = 1
            ORDER BY cb.nombre_banco ASC
        ");
        return $stmt->fetchAll();
    }

    public static function obtenerCuenta(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT cb.*, m.codigo AS moneda_codigo, m.simbolo AS moneda_simbolo
            FROM cuentas_bancarias cb
            INNER JOIN monedas m ON cb.moneda_id = m.id
            WHERE cb.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function registrarMovimiento(
        int $cuentaId,
        string $tipoMovimiento,
        string $numeroReferencia,
        float $monto,
        string $concepto,
        ?string $fecha = null,
        int $usuarioId = 1,
        float $tasaCambio = 1.0
    ): int {
        $db = Database::getConnection();
        try {
            Database::beginTransaction();

            $fechaMov = $fecha ?? date('Y-m-d');
            $stmt = $db->prepare("
                INSERT INTO movimientos_bancarios (
                    cuenta_id, tipo_movimiento, numero_referencia, monto, tasa_cambio,
                    fecha, conciliado, beneficiario_concepto, usuario_id
                ) VALUES (
                    :cta, :tipo, :ref, :monto, :tasa,
                    :fec, 1, :conc, :usr
                )
            ");
            $stmt->execute([
                'cta'   => $cuentaId,
                'tipo'  => $tipoMovimiento,
                'ref'   => $numeroReferencia,
                'monto' => $monto,
                'tasa'  => $tasaCambio,
                'fec'   => $fechaMov,
                'conc'  => $concepto,
                'usr'   => $usuarioId
            ]);
            $movId = (int)$db->lastInsertId();

            // Actualizar saldo de la cuenta bancaria
            $esEntrada = in_array($tipoMovimiento, ['DEPOSITO', 'TRANSFERENCIA_ENTRADA', 'COBRO_CLIENTE']);
            $signo = $esEntrada ? '+' : '-';

            $stmtUpd = $db->prepare("
                UPDATE cuentas_bancarias 
                SET saldo_actual = saldo_actual {$signo} :monto 
                WHERE id = :id
            ");
            $stmtUpd->execute(['monto' => $monto, 'id' => $cuentaId]);

            Database::commit();
            return $movId;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function transferir(
        int $cuentaOrigenId,
        int $cuentaDestinoId,
        float $monto,
        string $numeroReferencia,
        string $concepto,
        int $usuarioId = 1,
        float $tasaCambio = 1.0
    ): array {
        $db = Database::getConnection();
        try {
            Database::beginTransaction();

            // Salida de cuenta origen
            self::registrarMovimiento(
                $cuentaOrigenId,
                'TRANSFERENCIA_SALIDA',
                $numeroReferencia,
                $monto,
                "Transferencia Salida: {$concepto}",
                date('Y-m-d'),
                $usuarioId,
                $tasaCambio
            );

            // Entrada en cuenta destino
            self::registrarMovimiento(
                $cuentaDestinoId,
                'TRANSFERENCIA_ENTRADA',
                $numeroReferencia,
                $monto,
                "Transferencia Entrada: {$concepto}",
                date('Y-m-d'),
                $usuarioId,
                $tasaCambio
            );

            Database::commit();
            return [
                'status' => 'success',
                'message' => 'Transferencia interbancaria ejecutada correctamente.'
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
