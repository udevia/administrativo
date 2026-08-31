# MEMORIA TÉCNICA Y CONTEXTO COMPLETO DEL PROYECTO (mi)
**ERP Administrativo y Financiero Web - Enterprise Edition**

> Este documento contiene la memoria integral, arquitectura, decisiones técnicas, esquemas de datos, fórmulas de negocio y catálogo de endpoints del proyecto **mi**. Sirve como contexto completo para desarrolladores y modelos de Inteligencia Artificial que continúen trabajando en esta base de código en cualquier equipo.

---

## 1. Identidad y Filosofía del Sistema

El proyecto **mi** es una reimplementación moderna del clásico software de gestión empresarial **mi Software / a2 Administrativo**, adaptado a un entorno 100% Web:
- **Filosofía Zero-Bloat:** No depende de Node.js, Composer externo, Webpack ni frameworks PHP pesados (como Laravel o Symfony). Todo el núcleo corre sobre **PHP 8.0+ nativo** con un autoloader PSR-4 autocontenido.
- **Frontend Reactivo Ligero:** Las interfaces de usuario utilizan **Tailwind CSS** para estilizado estético tipo SaaS y **Alpine.js** para la reactividad de componentes (modales, cálculos matemáticos en vivo, filtros y tablas reactivas).
- **Manejo Dual de Moneda (USD / VES):** Soporte nativo para moneda funcional en Divisas (USD) con conversión en tiempo real a Bolívares (VES) usando la tasa oficial del día o tasas históricas fijadas.

---

## 2. Arquitectura del Núcleo (Backend & Core)

### A. Autoloader PSR-4 (`vendor/autoload.php`)
- Mapea el namespace raíz `App\` a la carpeta física `app/`.
- No requiere ejecutar `composer dump-autoload`. Detecta y carga dinámicamente cualquier clase, controlador o modelo agregado.

### B. Enrutador y Front Controller (`app/Core/Router.php` & `public/index.php`)
- Todas las peticiones HTTP ingresan por `public/index.php` (vía `.htaccess` en Apache o `-t public` con servidor PHP embebido).
- El enrutador procesa rutas estáticas y dinámicas con parámetros `{param}` para verbos `GET`, `POST`, `PUT`, `DELETE`.
- Provee el método `$router->view($path, $viewFile)` que renderiza vistas dentro del layout maestro `views/layout.php` usando buffers `ob_start()`.

### C. Conexión a Base de Datos (`app/Core/Database.php`)
- Patrón Singleton que administra una conexión `PDO` persistente.
- Configuración en UTF-8 multilingüe (`utf8mb4_unicode_ci`), modo de errores `PDO::ERRMODE_EXCEPTION` y soporte para multi-queries.

### D. Seguridad y Licenciamiento Digital RSA (`app/Core/License/LicenseValidator.php`)
- **Firma Asimétrica:** Utiliza criptografía **RSA de 2048 bits con algoritmo SHA-256**.
- **Llave Privada:** `config/keys/license_private_key.pem` (usada exclusivamente por `tools/generate_license.php` para emitir licencias).
- **Llave Pública:** `config/keys/license_public_key.pem` (embebida en el sistema para validar la firma de `license/sistema.lic`).
- **Inmutabilidad:** La Razón Social, RIF, tipo de licencia y módulos autorizados quedan sellados digitalmente; cualquier alteración del archivo invalida la firma y bloquea el acceso.

---

## 3. Esquema Relacional de Base de Datos (30 Migraciones)

Las migraciones se encuentran en `database/migrations/` ordenadas secuencialmente del `001` al `030`:

1. `001_tablas_maestras.sql`: `empresa`, `monedas`, `tasas_cambio`, `usuarios`, `depositos`.
2. `002_inventario_tables.sql`: `departamentos`, `categorias`, `unidades_medida`, `productos`, `inventario_existencias`, `kardex`.
3. `003_terceros_tables.sql`: `zonas`, `vendedores`, `clientes`, `proveedores`.
4. `004_operaciones_ventas.sql`: `ventas`, `ventas_detalles`, `ventas_pagos`, `caja_sesiones` (Aperturas y Cierres Z).
5. `005_operaciones_compras.sql`: `compras`, `compras_detalles`, `compras_pagos`.
6. `006_bancos_tesoreria.sql`: `cuentas_bancarias`, `movimientos_bancarios`, `recibos_cobranza`, `recibos_cobranza_detalles`.
7. `007_formatos_impresion.sql`: `formatos_impresion` (Plantillas de tickets 80mm y facturas carta con variables dinámicas).
8. `008_formatos_seeds.sql`: Semillas de plantillas HTML para ticket fiscal, factura y orden de entrega.
9. `013_vendedores_comisiones_preventa.sql`: `vendedores_comisiones_reglas`, metas de venta.
10. `014_familias_subfamilias.sql`: `familias`, `subfamilias` (Subdivisión de inventario).
11. `015_roles_permisos_matriz.sql`: `roles`, `permisos`, `roles_permisos`.
12. `016_recepciones_mercancia.sql`: `recepciones_mercancia`, `recepciones_detalles` (Circuito de almacén).
13. `018_productos_presentaciones.sql`: `productos_presentaciones` (Factores de conversión para bultos/cajas/unidades).
14. `019_contabilidad_integrada.sql`: `contabilidad_plan_cuentas` (PUC a 4 niveles), `contabilidad_mapeo_enlace`, `contabilidad_comprobantes`, `contabilidad_asientos_detalles`.
15. `020_contabilidad_profesional.sql`: `contador_clientes_empresas`, `contabilidad_periodos` (Multiempresa para el contador).
16. `021_nomina_integrada.sql`: `nomina_empleados`, `nomina_conceptos`, `nomina_periodos`, `nomina_recibos`, `nomina_recibos_detalles`.
17. `022_produccion_bom.sql`: `produccion_formulas`, `produccion_formulas_detalles`, `produccion_ordenes`, `produccion_ordenes_consumos`.
18. `023_ecommerce_integrado.sql`: `ecommerce_config`, `ecommerce_usuarios`, `ecommerce_pedidos`, `ecommerce_pedidos_detalles`.
19. `024_pasarela_bancaria_apis.sql`: `pasarelas_bancarias_config`, `pasarelas_transacciones_log` (Banesco y Banco Plaza C2P).
20. `025_modulo_servicios_sat.sql`: `sat_tipos_servicio`, `sat_campos_personalizados`, `sat_estados_flujo`, `sat_ordenes_trabajo`, `sat_ordenes_detalles`.
21. `026_productos_seriales.sql`: `productos_seriales` (Trazabilidad de números de serie y garantías).
22. `027_activos_fijos_depreciaciones.sql`: `activos_categorias`, `activos_fijos`, `activos_depreciaciones_mensuales`.
23. `028_auditoria_notificaciones.sql`: `auditoria_logs`, `notificaciones_cola`.
24. `029_modo_whatsapp.sql`: Configuración de proveedores Meta Cloud API / WhatsApp Web.
25. `030_telegram_bot_integration.sql`: `telegram_bot_config`, `telegram_usuarios_vinculados`.

---

## 4. Reglas de Negocio y Lógica de Dominio

### A. Listas de Precios A, B, C y D
- Todo producto posee un **Costo de Reposición (USD)** y 4 porcentajes de margen de utilidad parametrizables:
  - `Precio A` (PVP al detal) = `Costo * (1 + Utilidad_A / 100)`
  - `Precio B` (Precio al mayor) = `Costo * (1 + Utilidad_B / 100)`
  - `Precio C` (Distribuidor) = `Costo * (1 + Utilidad_C / 100)`
  - `Precio D` (Precio especial / institucional) = `Costo * (1 + Utilidad_D / 100)`
- Las ventas en el POS pueden seleccionar automáticamente la lista de precios según la ficha del cliente o asignar precios específicos.

### B. Circuito de Compras en 3 Fases
1. **Sugerencia / Orden de Compra (`ordenes_compra`):** Cálculo automatizado según existencias vs punto de reorden.
2. **Recepción en Almacén (`recepciones_mercancia`):** Incrementa el stock físico sin afectar la cuenta por pagar (CxP) hasta la conciliación.
3. **Factura de Compra CxP (`compras`):** Actualiza el costo promedio y costo de reposición del inventario, genera el pasivo con el proveedor y el comprobante contable automático.

### C. Contabilidad Automática (Partida Doble)
- **Venta al Contado:** DEBE: Caja/Bancos (Total) | HABER: Ventas (Base) + Débito Fiscal IVA.
- **Venta a Crédito:** DEBE: Cuentas por Cobrar Clientes | HABER: Ventas + Débito Fiscal IVA.
- **Nómina:** DEBE: Gastos de Personal (Asignaciones) | HABER: Retenciones IVSS/FAOV + Banco (Neto pagado).
- **Depreciación de Activos:** DEBE: Gasto Depreciación | HABER: Depreciación Acumulada.

### D. Nómina y Parafiscales
- Frecuencias de pago: Semanal, Quincenal o Mensual.
- Retenciones de ley: IVSS (4%), FAOV (1%), PIE (0.5%).
- Aportes patronales: IVSS (según nivel de riesgo 9-11%), FAOV (2%), INCES (2%).
- Cierre automatizado con emisión de comprobante de diario y recibos PDF individuales.

### E. SAT (Servicio de Asistencia Técnica / Taller)
- Tipos de servicio configurables (Mecánica Automotriz, Celulares, Laptops, Maquinaria).
- Metadatos dinámicos por tipo (Placa, Kilometraje, IMEI, Clave de desbloqueo, Nivel de Combustible).
- Facturación directa en el POS de la mano de obra + repuestos al completar la orden.

---

## 5. Catálogo de Vistas y Endpoints API

### A. Vistas del Sistema (Frontend)
- `/pos`: Punto de Venta / Facturación rápida en vivo.
- `/compras/sugerencias`: Asistente de sugerencia de compras y aprovisionamiento.
- `/maestros`: Hub central de archivos maestros.
- `/maestros/productos`: Catálogo maestro de productos con editor de precios A-D.
- `/maestros/clientes`: Ficha maestra de clientes con crédito y retenciones.
- `/maestros/proveedores`: Ficha de proveedores con retención ISLR.
- `/maestros/departamentos`: Departamentos y categorías.
- `/maestros/vendedores`: Asesores comerciales y comisiones.
- `/maestros/zonas`: Zonas geográficas y rutas.
- `/maestros/monedas`: Monedas duales y tasa del día.
- `/maestros/depositos`: Almacenes y depósitos.
- `/maestros/bancos`: Cuentas bancarias y cajas.
- `/contabilidad/panel`: Comprobantes contables y balance de comprobación.
- `/contador/multicliente`: Panel para contadores externos multiempresa.
- `/nomina/panel`: Procesamiento de prenómina y cierres.
- `/sat/panel`: Gestión de órdenes de taller y servicio técnico.
- `/produccion/panel`: Fórmulas de ensamble y órdenes de producción.
- `/activos/panel`: Ficha de activos fijos y depreciación mensual.
- `/reportes/suite`: Estadísticas y reportes operacionales estilo a2.
- `/configuracion/formatos`: Diseñador de tickets térmicos y facturas.
- `/configuracion/migracion`: Asistente de importación masiva CSV.
- `/configuracion/whatsapp`: Configuración de notificaciones.
- `/auditoria/logs`: Visor de trazabilidad y logs.
- `/tienda`: Portal e-commerce público.

### B. Endpoints API REST Principales
- `GET/POST /api/maestros/productos`: Consulta y guardado de productos.
- `GET/POST /api/maestros/clientes`: Consulta y guardado de clientes.
- `GET/POST /api/maestros/proveedores`: Consulta y guardado de proveedores.
- `GET/POST /api/maestros/departamentos`: Gestión departamental.
- `GET/POST /api/maestros/vendedores`: Asesores y comisiones.
- `GET/POST /api/maestros/zonas`: Zonas geográficas.
- `GET/POST /api/maestros/monedas/tasa`: Actualización de la tasa oficial del día.
- `GET/POST /api/maestros/depositos`: Gestión de almacenes.
- `GET/POST /api/maestros/bancos`: Cuentas y cajas.
- `POST /api/pos/procesar-venta`: Registro atómico de ventas y apartado de stock.
- `GET /api/pos/resumen-cierre-z`: Métricas para el reporte fiscal de cierre Z.
- `POST /api/sat/crear` / `POST /api/sat/facturar`: Flujo de órdenes de servicio técnico.
- `POST /api/nomina/cierre`: Procesamiento de nómina con asiento contable y banco.
- `POST /api/activos-fijos/depreciar-mensual`: Depreciación periódica.
- `POST /api/migracion/analizar` / `POST /api/migracion/ejecutar`: Carga masiva CSV.

---

## 6. Configuración de Archivos y Scripts de Utilidad

1. **`iniciar_servidor.bat`:** Script en la raíz para iniciar el servidor web PHP en el puerto 8000.
2. **`generar_licencia.bat`:** Script en la raíz para emitir licencias `.lic` firmadas con RSA-SHA256.
3. **`config/database.php`:** Archivo con las credenciales de base de datos creadas por el instalador.
4. **`storage/installed.lock`:** Archivo testigo que indica que el sistema está instalado. Si se borra, el sistema vuelve a entrar en modo `/installer`.

---
*Fin de la memoria técnica del proyecto mi.*
