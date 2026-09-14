# Mapa de Fase 1 — mi Administrativo Comercial v1

## 1. Propósito

Construir una primera versión estable y operativa para comercios y pequeñas/medianas empresas venezolanas.

La Fase 1 debe resolver correctamente el ciclo comercial principal:

```text
Seguridad
  ↓
Maestros
  ↓
Compras
  ↓
Inventario
  ↓
Ventas / POS
  ↓
Caja, bancos y crédito
  ↓
Reportes y auditoría
```

El objetivo no es incluir todos los módulos del ERP, sino entregar un núcleo confiable, trazable y preparado para evolucionar.

---

## 2. Alcance de la Fase 1

### Incluido

1. Instalación y configuración base.
2. Usuarios, autenticación y permisos.
3. Empresas, monedas y tasas.
4. Productos y categorías.
5. Clientes.
6. Proveedores.
7. Depósitos.
8. Vendedores.
9. Compras.
10. Inventario y kardex.
11. Ventas y POS.
12. Cajas y bancos básicos.
13. Cuentas por cobrar y pagar básicas.
14. Reportes operativos.
15. Auditoría.
16. Respaldos básicos.

### Fuera de alcance temporal

- Nómina avanzada.
- Producción y BOM.
- SAT/taller.
- E-commerce.
- Preventa móvil.
- WhatsApp y Telegram.
- Pasarelas bancarias automáticas.
- Multiempresa avanzada.
- Sincronización nube/local.
- FIFO/PEPS avanzado.
- Gestión avanzada de lotes y seriales.
- Licenciamiento comercial definitivo.

Estos módulos no se eliminan; quedan reservados para fases posteriores.

---

## 3. Arquitectura objetivo

```text
public/index.php
      ↓
Router / Middleware
      ↓
Autenticación y autorización
      ↓
Controllers
      ↓
Services de dominio
      ↓
PDO / Database
      ↓
MySQL o MariaDB
```

### Reglas técnicas

- Todas las solicitudes pasan por el front controller.
- Toda operación administrativa requiere usuario autenticado.
- Toda operación de escritura debe validar permisos.
- Toda operación POST, PUT o DELETE debe validar CSRF.
- Toda transacción comercial debe ejecutarse dentro de una transacción de base de datos.
- El usuario real debe quedar registrado en cada operación.
- Los errores internos se registran, pero no se exponen directamente al usuario.
- Las vistas no deben contener lógica de negocio crítica.
- Las consultas nuevas deben utilizar parámetros preparados.

---

## 4. Bloques de trabajo

## Bloque A — Base técnica y arranque

### Objetivo

Garantizar que una instalación limpia pueda arrancar de forma reproducible.

### Tareas

- Resolver la dependencia de `vendor/autoload.php`.
- Definir oficialmente si se utilizará Composer o un autoloader nativo.
- Validar versión PHP y extensiones requeridas.
- Revisar configuración de base de datos.
- Separar configuración de desarrollo y producción.
- Crear instalador idempotente.
- Crear archivo de instalación bloqueado después del setup.
- Revisar permisos de carpetas `storage/`, `config/` y `license/`.
- Retirar la clave privada RSA del repositorio.
- Dejar únicamente la clave pública en el servidor de aplicación.

### Criterio de finalización

Una instalación nueva debe poder:

1. Configurar la base de datos.
2. Ejecutar todas las migraciones en orden.
3. Crear el administrador inicial.
4. Activar el sistema.
5. Impedir el acceso posterior al instalador.
6. Cargar una página protegida correctamente.

---

## Bloque B — Seguridad y usuarios

### Objetivo

Evitar que cualquier persona pueda acceder o ejecutar operaciones administrativas.

### Componentes

- Inicio de sesión.
- Cierre de sesión.
- Sesión segura.
- Expiración de sesión.
- Roles.
- Permisos.
- Usuario administrador.
- Cambio de contraseña.
- Protección CSRF.
- Middleware de autenticación.
- Middleware de autorización.
- Auditoría de accesos.

### Roles iniciales

#### Administrador

Acceso completo al sistema.

#### Supervisor

Acceso a operaciones y reportes, con autorización especial para descuentos, anulaciones y ajustes.

#### Vendedor

Acceso al POS y a consultas operativas limitadas.

#### Consulta

Acceso únicamente a reportes autorizados.

### Criterio de finalización

- Una ruta protegida no puede ser usada sin sesión.
- Un usuario sin permiso recibe HTTP 403.
- Las contraseñas nunca se almacenan en texto plano.
- Los formularios POST no funcionan sin token CSRF válido.
- Las operaciones quedan vinculadas al usuario autenticado.

---

## Bloque C — Maestros

### Entidades principales

```text
Empresa
 ├── Monedas
 ├── Tasas de cambio
 ├── Depósitos
 ├── Cajas y bancos
 ├── Categorías
 ├── Productos
 ├── Clientes
 ├── Proveedores
 └── Vendedores
```

### Producto mínimo

- Código.
- Código de barras.
- Descripción.
- Categoría.
- Unidad de medida.
- Costo.
- Precio de venta.
- Impuesto.
- Estado.
- Existencia por depósito.
- Punto de reorden.

### Cliente mínimo

- Tipo de documento.
- Documento fiscal.
- Razón social o nombre.
- Teléfono.
- Correo.
- Dirección.
- Condición de pago.
- Límite de crédito.
- Estado.

### Proveedor mínimo

- Documento fiscal.
- Razón social.
- Teléfono.
- Correo.
- Dirección.
- Condición de pago.
- Estado.

### Criterio de finalización

Todas las entidades deben tener:

- Alta.
- Consulta.
- Edición.
- Activación/desactivación.
- Validación de campos obligatorios.
- Auditoría de cambios.

---

## Bloque D — Inventario

### Objetivo

Mantener existencias correctas y trazables por depósito.

### Operaciones

- Entrada por compra.
- Salida por venta.
- Ajuste positivo.
- Ajuste negativo.
- Traslado entre depósitos.
- Consulta de existencia.
- Kardex.
- Stock mínimo.
- Existencia comprometida básica.

### Reglas

- No permitir stock negativo, salvo permiso explícito.
- Todo movimiento debe indicar usuario, fecha, depósito y motivo.
- Las ventas confirmadas deben descontar inventario atómicamente.
- Las anulaciones deben revertir el movimiento original.
- Los ajustes deben exigir motivo.
- El kardex no debe modificarse físicamente; se corrige mediante movimientos inversos.

### Criterio de finalización

El stock mostrado en el producto debe coincidir con la suma de movimientos y existencias por depósito.

---

## Bloque E — Compras

### Flujo inicial

```text
Proveedor
  ↓
Factura de compra
  ↓
Detalle de productos
  ↓
Impuestos y totales
  ↓
Recepción
  ↓
Entrada de inventario
  ↓
Pago o cuenta por pagar
```

### Operaciones

- Crear compra.
- Registrar factura.
- Recibir mercancía.
- Actualizar costo.
- Actualizar inventario.
- Registrar cuenta por pagar.
- Registrar pago.
- Consultar historial.
- Anular compra bajo autorización.

### Criterio de finalización

Confirmar una compra debe actualizar en la misma transacción:

1. Cabecera de compra.
2. Detalles.
3. Existencia.
4. Kardex.
5. Cuenta por pagar o salida de caja.
6. Auditoría.

---

## Bloque F — Ventas y POS

### Flujo inicial

```text
Cliente
  ↓
Carrito
  ↓
Productos y cantidades
  ↓
Precios y descuentos
  ↓
Impuestos
  ↓
Formas de pago
  ↓
Confirmación
  ↓
Inventario + caja/cuenta por cobrar
  ↓
Comprobante
```

### Funcionalidades

- Buscar por código de barras.
- Buscar por descripción.
- Agregar y retirar productos.
- Modificar cantidades.
- Seleccionar cliente.
- Aplicar lista de precios.
- Aplicar descuento autorizado.
- Calcular IVA.
- Cobrar en USD.
- Cobrar en VES.
- Registrar pago mixto.
- Registrar crédito.
- Imprimir o descargar comprobante.
- Consultar historial.
- Anular con permiso.
- Cerrar caja.

### Criterio de finalización

Confirmar una venta debe actualizar atómicamente:

1. Cabecera de venta.
2. Detalles.
3. Existencia.
4. Kardex.
5. Caja o banco.
6. Cuenta por cobrar si aplica.
7. Auditoría.

---

## Bloque G — Tesorería y crédito

### Tesorería básica

- Caja general.
- Cajas por usuario o punto de venta.
- Bancos.
- Ingresos.
- Egresos.
- Transferencias.
- Movimientos manuales autorizados.
- Cierre de caja.

### Crédito

- Cuenta corriente del cliente.
- Cuenta corriente del proveedor.
- Abonos.
- Pagos.
- Saldo pendiente.
- Vencimientos.
- Estado de cuenta.

### Criterio de finalización

Los saldos deben poder conciliarse con las operaciones que los originaron.

---

## Bloque H — Reportes y auditoría

### Reportes mínimos

- Ventas diarias.
- Ventas por período.
- Ventas por vendedor.
- Ventas por producto.
- Compras por período.
- Existencias actuales.
- Productos bajo mínimo.
- Kardex.
- Cuentas por cobrar.
- Cuentas por pagar.
- Movimientos de caja.
- Cierre diario.

### Auditoría mínima

Registrar:

- Usuario.
- IP.
- Fecha y hora.
- Módulo.
- Acción.
- Registro afectado.
- Valores anteriores.
- Valores nuevos.
- Resultado.

---

## 5. Modelo de datos prioritario

Las tablas principales de la Fase 1 son:

```text
usuarios
roles
permisos
roles_permisos
empresa
monedas
tasas_cambio
departamentos
categorias
productos
producto_deposito
clientes
proveedores
vendedores
depositos
cuentas_bancarias
ventas
ventas_detalles
compras
compras_detalles
kardex_inventario
cuentas_por_cobrar
cuentas_por_pagar
recibos_cobranza
movimientos_bancarios
auditoria_logs
```

Las tablas especializadas de nómina, producción, SAT, e-commerce y sincronización quedan fuera del flujo obligatorio de la primera versión.

---

## 6. Rutas principales objetivo

### Autenticación

```text
GET  /login
POST /login
POST /logout
GET  /perfil
POST /perfil/password
```

### Maestros

```text
GET  /maestros
GET  /maestros/productos
GET  /maestros/clientes
GET  /maestros/proveedores
GET  /maestros/depositos
GET  /maestros/bancos
```

### APIs comerciales

```text
GET  /api/maestros/productos
POST /api/maestros/productos
GET  /api/maestros/clientes
POST /api/maestros/clientes
GET  /api/maestros/proveedores
POST /api/maestros/proveedores

GET  /api/inventario/productos
GET  /api/inventario/kardex/{id}
POST /api/inventario/ajuste
POST /api/inventario/traslado

POST /api/compras/procesar-factura
GET  /api/compras/historial

POST /api/pos/procesar-venta
GET  /api/ventas/historial
POST /api/ventas/{id}/anular

GET  /api/cxc/clientes/{id}
POST /api/cxc/recibo
GET  /api/cxp/proveedores/{id}
POST /api/cxp/pago
```

Todas las rutas administrativas deben pasar por autenticación y autorización.

---

## 7. Orden de implementación

### Sprint 1 — Arranque y seguridad base

- Autoload.
- Configuración.
- Instalador.
- Sesiones.
- Login.
- Roles.
- CSRF.
- Middleware.

### Sprint 2 — Base de datos y maestros

- Limpieza de migraciones.
- Corrección de nombres heredados.
- Productos.
- Clientes.
- Proveedores.
- Depósitos.
- Monedas y tasas.

### Sprint 3 — Inventario

- Existencias.
- Entradas.
- Salidas.
- Ajustes.
- Traslados.
- Kardex.

### Sprint 4 — Compras

- Factura de compra.
- Recepción.
- Entrada de mercancía.
- Cuentas por pagar.
- Pagos.

### Sprint 5 — Ventas y POS

- Carrito.
- Precios.
- Impuestos.
- Pagos mixtos.
- Crédito.
- Impresión.
- Anulaciones.

### Sprint 6 — Tesorería y reportes

- Caja.
- Bancos.
- Cierres.
- Cuentas por cobrar.
- Reportes.
- Auditoría.

### Sprint 7 — Pruebas y endurecimiento

- Pruebas de integración.
- Pruebas E2E.
- Pruebas de permisos.
- Pruebas de concurrencia.
- Revisión de errores.
- Revisión de respaldos.
- Manual de usuario.

---

## 8. Pruebas de aceptación

La Fase 1 se considera aprobada cuando se puedan ejecutar estos escenarios:

### Escenario 1 — Seguridad

- Crear usuario vendedor.
- Iniciar sesión.
- Intentar acceder a una función no autorizada.
- Confirmar respuesta 403.
- Cerrar sesión.
- Confirmar que la ruta protegida ya no es accesible.

### Escenario 2 — Compra e inventario

- Crear proveedor.
- Crear producto.
- Registrar compra de 10 unidades.
- Confirmar recepción.
- Verificar existencia de 10 unidades.
- Verificar entrada en kardex.
- Verificar cuenta por pagar.

### Escenario 3 — Venta de contado

- Crear venta de 3 unidades.
- Registrar pago.
- Verificar existencia de 7 unidades.
- Verificar salida en kardex.
- Verificar ingreso en caja.
- Verificar comprobante.

### Escenario 4 — Venta a crédito

- Registrar venta a crédito.
- Verificar saldo del cliente.
- Registrar abono parcial.
- Verificar saldo restante.

### Escenario 5 — Anulación

- Anular una venta autorizadamente.
- Verificar reverso de inventario.
- Verificar reverso de caja o crédito.
- Verificar auditoría.

### Escenario 6 — Cierre

- Registrar ventas y movimientos de caja.
- Ejecutar cierre diario.
- Verificar totales por forma de pago.
- Verificar diferencias y usuario responsable.

---

## 9. Indicadores de calidad

Antes de declarar terminada la Fase 1 se debe cumplir:

- 0 errores de nombres de tablas en el flujo comercial.
- 0 rutas administrativas sin autenticación.
- 0 contraseñas almacenadas en texto plano.
- 0 claves privadas dentro del repositorio.
- 100% de operaciones comerciales con usuario y auditoría.
- 100% de ventas y compras con transacciones.
- Instalación limpia reproducible.
- Pruebas de aceptación ejecutadas correctamente.
- Documentación de instalación actualizada.
- Documentación de operación básica disponible.

---

## 10. Evolución posterior

Una vez estable el núcleo, las siguientes fases se organizarán así:

### Fase 2 — Contabilidad integrada

- Plan de cuentas único.
- Asientos automáticos.
- IVA.
- Retenciones.
- Balance de comprobación.
- Cierres contables.

### Fase 3 — Nómina y activos

- Empleados.
- Prenómina.
- Deducciones.
- Aportes patronales.
- Recibos.
- Activos fijos.
- Depreciaciones.

### Fase 4 — Producción y SAT

- BOM.
- Órdenes de producción.
- Consumo de materiales.
- Taller.
- Órdenes de servicio.

### Fase 5 — Canales digitales

- E-commerce.
- Preventa.
- WhatsApp.
- Telegram.
- Pasarelas bancarias.

### Fase 6 — Enterprise

- Multiempresa.
- Panel contable multicliente.
- Sincronización nube/local.
- Alta disponibilidad.
- Reportes avanzados.
- Licenciamiento comercial definitivo.
