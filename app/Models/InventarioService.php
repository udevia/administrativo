<?php 
namespace App\Models; 
 
use App\Core\Database; 
use PDO; 
use Exception; 
 
class InventarioService { 
    public static function registrarMovimiento( 
        int $productoId, 
        int $depositoId, 
        string $tipoMovimiento, 
        string $docTipo, 
        string $docNumero, 
        float $cantidad, 
        float $costoUnitario, 
        int $usuarioId, 
        string $nota = '' 
    ): void { 
        $db = Database::getConnection(); 
 
        // Obtener o inicializar registro en producto_deposito con bloqueo pesimista 
        $stmt = $db->prepare("SELECT existencia FROM producto_deposito WHERE producto_id = :p AND deposito_id = :d FOR UPDATE"); 
        $stmt->execute(['p' => $productoId, 'd' => $depositoId]); 
        $row = $stmt->fetch(); 
 
        if (!$row) { 
            $db->prepare("INSERT INTO producto_deposito (producto_id, deposito_id, existencia) VALUES (:p, :d, 0)") 
               ->execute(['p' => $productoId, 'd' => $depositoId]); 
            $existenciaAnterior = 0.0; 
        } else { 
            $existenciaAnterior = (float)$row['existencia']; 
        } 
 
        $esEntrada = in_array($tipoMovimiento, ['ENTRADA_COMPRA', 'AJUSTE_POSITIVO', 'TRASLADO_DESTINO']); 
        $esSalida  = in_array($tipoMovimiento, ['SALIDA_VENTA', 'AJUSTE_NEGATIVO', 'TRASLADO_ORIGEN']); 
 
        if ($esEntrada) { 
            $existenciaPosterior = $existenciaAnterior + $cantidad; 
        } elseif ($esSalida) { 
            if ($existenciaAnterior < $cantidad) { 
                throw new Exception("Stock insuficiente en el depósito. Disponible: {$existenciaAnterior}, Requerido: {$cantidad}"); 
            } 
            $existenciaPosterior = $existenciaAnterior - $cantidad; 
        } else { 
            throw new Exception("Tipo de movimiento desconocido."); 
        } 
 
        // Actualizar stock del depósito 
        $stmtUpdate = $db->prepare("UPDATE producto_deposito SET existencia = :stock WHERE producto_id = :p AND deposito_id = :d"); 
        $stmtUpdate->execute(['stock' => $existenciaPosterior, 'p' => $productoId, 'd' => $depositoId]); 
 
        // Registrar en Kardex 
        $stmtKardex = $db->prepare(" 
            INSERT INTO kardex_inventario  
            (producto_id, deposito_id, tipo_movimiento, documento_tipo, documento_numero, cantidad, costo_unitario, existencia_anterior, existencia_posterior, usuario_id, nota) 
            VALUES (:p, :d, :tm, :dt, :dn, :cant, :costo, :ea, :ep, :u, :nota) 
        "); 
        $stmtKardex->execute([ 
            'p'     => $productoId, 
            'd'     => $depositoId, 
            'tm'    => $tipoMovimiento, 
            'dt'    => $docTipo, 
            'dn'    => $docNumero, 
            'cant'  => $cantidad, 
            'costo' => $costoUnitario, 
            'ea'    => $existenciaAnterior, 
            'ep'    => $existenciaPosterior, 
            'u'     => $usuarioId, 
            'nota'  => $nota 
        ]); 
 
        // Si es una compra, recalcular costo promedio y último costo 
        if ($tipoMovimiento === 'ENTRADA_COMPRA') { 
            self::actualizarCostosProducto($productoId, $cantidad, $costoUnitario, $existenciaAnterior); 
        } 
    } 
 
    private static function actualizarCostosProducto(int $productoId, float $cantidadEntrada, float $costoEntrada, float $stockPrevioTotal): void { 
        $db = Database::getConnection(); 
        $stmt = $db->prepare("SELECT costo_promedio FROM productos WHERE id = :id"); 
        $stmt->execute(['id' => $productoId]); 
        $prod = $stmt->fetch(); 
 
        $costoPromedioActual = (float)($prod['costo_promedio'] ?? 0); 
        $totalStockNuevo = $stockPrevioTotal + $cantidadEntrada; 
 
        if ($totalStockNuevo > 0) { 
            $nuevoCostoPromedio = (($stockPrevioTotal * $costoPromedioActual) + ($cantidadEntrada * $costoEntrada)) / $totalStockNuevo; 
        } else { 
            $nuevoCostoPromedio = $costoEntrada; 
        } 
 
        $stmtUpd = $db->prepare(" 
            UPDATE productos  
            SET costo_ultimo = :costo_ultimo, costo_promedio = :costo_promedio  
            WHERE id = :id 
        "); 
        $stmtUpd->execute([ 
            'costo_ultimo'   => $costoEntrada, 
            'costo_promedio' => round($nuevoCostoPromedio, 4), 
            'id'             => $productoId 
        ]); 
    } 
} 