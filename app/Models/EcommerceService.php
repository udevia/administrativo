<?php
namespace App\Models;
use App\Core\Database;
use App\Models\DocumentoVentaService;
use Exception;
class EcommerceService {
   /**
    * Catálogo público con stock real disponible y precios según rol del usuario
    */
    public static function getCatalogoPublico(?int $usuarioWebId = null, ?int $categoriaId = null, string $busqueda = ''): array {
       $db = Database::getConnection();
       // 1. Obtener lista de precios a aplicar
       $listaPrecio = 'A';
       if ($usuarioWebId) {
           $stmtUser = $db->prepare("
               SELECT c.lista_precio_default 
               FROM ecommerce_usuarios eu
               INNER JOIN clientes c ON eu.cliente_id = c.id
               WHERE eu.id = :uid
           ");
           $stmtUser->execute(['uid' => $usuarioWebId]);
           $listaCliente = $stmtUser->fetchColumn();
           if ($listaCliente) $listaPrecio = strtoupper($listaCliente);
       }
       $campoPrecio = 'p.precio_' . strtolower($listaPrecio);
       // 2. Consulta de productos con existencias disponibles
       $sql = "
           SELECT 
               p.id, p.codigo, p.codigo_barra, p.descripcion, p.imagen_url,
               p.porcentaje_iva, p.exento_iva,
               {$campoPrecio} AS precio_venta,
               COALESCE(SUM(pd.existencia - pd.existencia_comprometida), 0) AS stock_disponible,
               c.descripcion AS categoria_nombre
           FROM productos p
           LEFT JOIN producto_deposito pd ON p.id = pd.producto_id
           LEFT JOIN categorias c ON p.categoria_id = c.id
           WHERE p.estado = 1 AND p.publicar_en_ecommerce = 1
       ";
       $params = [];
       if ($categoriaId) {
           $sql .= " AND p.categoria_id = :cat";

           $params['cat'] = $categoriaId;
       }
       if (!empty($busqueda)) {
           $sql .= " AND (p.descripcion LIKE :q OR p.codigo LIKE :q OR p.codigo_barra LIKE :q)";
           $params['q'] = "%{$busqueda}%";
       }
       $sql .= " GROUP BY p.id HAVING stock_disponible > 0 ORDER BY p.descripcion ASC";
       $stmt = $db->prepare($sql);
       $stmt->execute($params);
       return $stmt->fetchAll();
   }
   /**
    * Procesa la compra online: genera Apartado en inventario y crea el pedido web
    */
   public static function procesarPedidoWeb(array $datos): array {
       $db = Database::getConnection();
       try {
           Database::beginTransaction();
           $usuarioWebId = (int)$datos['usuario_web_id'];
           $items        = $datos['items'] ?? [];
           $metodoPago   = $datos['metodo_pago'];
           $referencia   = trim($datos['referencia_pago'] ?? '');
           $tasaCambio   = (float)($datos['tasa_cambio'] ?? Database::getTasaActualUsd());
           // Obtener o crear cliente fiscal asociado
           $stmtU = $db->prepare("SELECT * FROM ecommerce_usuarios WHERE id = :id");
           $stmtU->execute(['id' => $usuarioWebId]);
           $usr = $stmtU->fetch();
           if (!$usr) throw new Exception("Usuario web no encontrado.");
           $clienteId = $usr['cliente_id'];
           if (!$clienteId) {
               // Crear cliente B2C automático en la tabla clientes
                $stmtCli = $db->prepare("
                    INSERT INTO clientes (codigo, razon_social, documento_fiscal, direccion_fiscal, telefono, email, lista_precio_default, estado)
                    VALUES (:cod, :nom, :doc, :dir, :tel, :em, 'A', 1)
                ");
                $codigoCli = 'WEB-' . date('ymd') . '-' . rand(10, 99);
                $stmtCli->execute([
                    'cod' => $codigoCli,
                    'nom' => $usr['nombre_completo'],
                    'doc' => $usr['documento_identidad'],
                    'dir' => $usr['direccion_entrega'],
                    'tel' => $usr['telefono'],
                    'em'  => $usr['email']
                ]);
                $clienteId = (int)$db->lastInsertId();
                $db->prepare("UPDATE ecommerce_usuarios SET cliente_id = :cid WHERE id = :uid")->execute(['cid' => $clienteId, 'uid' => $usuarioWebId]);
            }
           // 1. Generar APARTADO formal en el core de ventas para reservar stock
           $numApartado = 'WEB-ORD-' . date('ymd') . '-' . rand(100, 999);
           $cabeceraVenta = [
               'tipo_documento'   => 'APARTADO',
               'numero_documento' => $numApartado,
               'cliente_id'       => $clienteId,
               'deposito_id'      => 1,
               'usuario_id'       => 1,
               'fecha_emision'    => date('Y-m-d'),
               'fecha_vencimiento'=> date('Y-m-d', strtotime('+48 hours')), // 48 horas para validar pago
               'condicion_pago'   => ($metodoPago === 'CREDITO_B2B') ? 'CREDITO' : 'CONTADO',
               'moneda_id'        => 1,
               'tasa_cambio'      => $tasaCambio,
               'nota'             => "Pedido Web {$numApartado} | Pago: {$metodoPago} | Ref: {$referencia}"
           ];
           $resApartado = VentasService::procesarVenta($cabeceraVenta, $items);
           $ventaApartadoId = (int)$resApartado['venta_id'];

           // 2. Registrar en la tabla de ecommerce
           $totalUsd = (float)($resApartado['total'] ?? 0);
           $totalBs  = $totalUsd * $tasaCambio;
           $stmtOrd = $db->prepare("
               INSERT INTO ecommerce_pedidos 
               (numero_orden_web, usuario_web_id, venta_apartado_id, metodo_pago, referencia_pago, monto_total_usd, tasa_cambio, monto_total_bs, estado_pago, estado_despacho)
               VALUES (:num, :uid, :vid, :met, :ref, :usd, :tasa, :bs, 'PENDIENTE_REVISION', 'RECIBIDO')
           ");
           $stmtOrd->execute([
               'num'  => $numApartado,
               'uid'  => $usuarioWebId,
               'vid'  => $ventaApartadoId,
               'met'  => $metodoPago,
               'ref'  => $referencia,
               'usd'  => $totalUsd,
               'tasa' => $tasaCambio,
               'bs'   => $totalBs
           ]);
           $pedidoWebId = (int)$db->lastInsertId();
           Database::commit();
           return [
               'status'           => 'success',
               'pedido_id'        => $pedidoWebId,
               'numero_orden'     => $numApartado,
               'monto_total_usd'  => $totalUsd,
               'mensaje'          => 'Su pedido ha sido recibido y el inventario ha sido reservado. Procesaremos el despacho.'
           ];
       } catch (Exception $e) {
           Database::rollBack();
           throw $e;
       }
   }
}