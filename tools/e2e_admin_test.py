import urllib.request
import json
import time

BASE_URL = "http://localhost:8000"
TS = str(int(time.time()))

def get(path):
    try:
        req = urllib.request.Request(BASE_URL + path, method='GET')
        with urllib.request.urlopen(req, timeout=10) as response:
            return json.loads(response.read().decode())
    except Exception as e:
        if hasattr(e, 'read'):
            return {"status": "error", "message": e.read().decode()}
        return {"status": "error", "message": str(e)}

def post(path, data):
    try:
        req = urllib.request.Request(BASE_URL + path, data=json.dumps(data).encode(), headers={'Content-Type': 'application/json'})
        with urllib.request.urlopen(req, timeout=10) as response:
            return json.loads(response.read().decode())
    except Exception as e:
        if hasattr(e, 'read'):
            return {"status": "error", "message": e.read().decode()}
        return {"status": "error", "message": str(e)}

print("=== STARTING ADMIN E2E EVALUATION ===\n")

print("1. CONFIGURACION INICIAL (MAESTROS)")
prov = post('/api/maestros/proveedores', {
    'documento_fiscal': f'J-{TS}',
    'razon_social': 'Apple Inc. Distribuidores',
    'condicion_pago': 'CREDITO_30',
    'limite_credito': 50000.00
})
print("Proveedor ->", prov)

cli = post('/api/maestros/clientes', {
    'documento_fiscal': f'V-{TS}',
    'razon_social': 'Empresa Demo C.A.',
    'condicion_pago': 'CONTADO',
    'limite_credito': 0
})
print("Cliente ->", cli)

prod = post('/api/maestros/productos', {
    'codigo': f'PRD-{TS}',
    'nombre': 'iPhone 16 Pro Max 256GB Titanium',
    'categoria_id': 1,
    'departamento_id': 1,
    'familia_id': 1,
    'tipo_producto': 'FISICO_SERIALIZADO',
    'maneja_lotes': False,
    'maneja_seriales': True,
    'costo_promedio_usd': 950.00,
    'precio_venta_1_usd': 1235.00,
    'precio_venta_2_usd': 1200.00,
    'precio_venta_3_usd': 1180.00
})
print("Producto ->", prod)
prod_id = prod.get('id', 1)

print("\n2. COMPRAS E INVENTARIO")
compra_data = {
    'cabecera': {
        'proveedor_id': prov.get('id', 1),
        'deposito_id': 1,
        'numero_documento': f'FAC-SUP-{TS}',
        'documento_referencia': f'FAC-SUP-{TS}',
        'numero_factura': f'FAC-SUP-{TS}',
        'total': 47500.00,
        'tipo_documento': 'FACTURA',
        'condicion_pago': 'CONTADO'
    },
    'items': [
        {
            'producto_id': prod_id,
            'cantidad': 50,
            'costo_unitario': 950.00,
            'seriales': [{'numero_serial': f'SN-{TS}-{i}'} for i in range(50)]
        }
    ]
}
compra = post('/api/compras/procesar-factura', compra_data)
print("Compra 50 unidades ->", compra)

stock = get(f'/api/inventario/kardex/{prod_id}')
print("Stock del Producto ->", stock)

print("\n3. VENTAS POS")
venta_data = {
    'cabecera': {
        'tipo_documento': 'FACTURA',
        'numero_documento': f'FAC-{TS}',
        'condicion_pago': 'CONTADO',
        'cliente_id': cli.get('id', 1),
        'deposito_id': 1,
        'totales': {
            'subtotal': 2470.00,
            'exento': 0,
            'baseImponible': 2470.00,
            'iva': 395.20,
            'igtf': 0,
            'total': 2865.20
        },
        'cobro': {
            'efectivo_usd': 1400.00,
            'zelle': 1465.20,
            'vuelto_usd': 0
        }
    },
    'items': [
        {
            'producto_id': prod_id,
            'cantidad': 2,
            'precio_unitario': 1235.00,
            'serial_seleccionado': f'SN-{TS}-0'
        }
    ]
}
venta = post('/api/pos/procesar-venta', venta_data)
print("Venta POS 2 unidades ->", venta)

print("\n4. RECURSOS HUMANOS (NOMINA)")
emp = post('/api/nomina/empleados', {
    'codigo': f'EMP-{TS}',
    'cedula': f'V-{TS}',
    'nombres': 'Carlos',
    'apellidos': 'Gomez',
    'cargo_nombre': 'Vendedor Senior',
    'fecha_ingreso': '2023-01-15',
    'sueldo_base_mensual': 350.00,
    'bono_alimentacion_mensual': 40.00
})
print("Empleado Registrado ->", emp)
prenomina = post('/api/nomina/prenomina', {
    'codigo_periodo': f'Q1-{TS}',
    'fecha_inicio': '2023-01-01',
    'fecha_fin': '2023-01-15',
    'fecha_pago': '2023-01-15',
    'frecuencia': 'QUINCENAL',
    'usuario_id': 1
})
print("Prenomina Calculada ->", prenomina)

print("\n5. ACTIVOS FIJOS")
activo = post('/api/activos-fijos/registrar', {
    'codigo_placa_activo': f'ACT-{TS}',
    'categoria_id': 1,
    'departamento_id': 1,
    'descripcion': 'Servidor Dell PowerEdge',
    'costo_adquisicion_usd': 2500.00,
    'vida_util_meses': 60,
    'fecha_adquisicion': '2023-01-01',
    'fecha_inicio_depreciacion': '2023-01-01'
})
print("Activo Fijo Registrado ->", activo)
deprec = post('/api/activos-fijos/depreciar-mensual', {
    'ano': 2023,
    'mes': 1
})
print("Depreciacion Ejecutada ->", deprec)

print("\n6. REPORTES GERENCIALES")
rep_ventas = get('/api/reportes/ventas-clientes')
print("Reporte Ventas ->", rep_ventas.get('status'), "- Filas:", len(rep_ventas.get('data', [])) if type(rep_ventas.get('data')) == list else 0)
rep_inv = get('/api/reportes/inventario-valorizado')
print("Reporte Inv Valorizado ->", rep_inv.get('status'), "- Filas:", len(rep_inv.get('data', [])) if type(rep_inv.get('data')) == list else 0)

print("\n=== E2E TEST COMPLETE ===")
