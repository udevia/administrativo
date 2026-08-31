# Guía Completa de Referencia y Documentación Técnica del Sistema mi
**ERP Administrativo y Financiero Web (Suite a2)**

> **Nota para migración a otro equipo:** Al copiar la carpeta `mi` a otra computadora con PHP 8.0+ y MySQL/MariaDB (XAMPP), este documento contiene todos los detalles necesarios para su ejecución inmediata.

---

## 1. Resumen Ejecutivo del Proyecto

El sistema **mi** es un software ERP / Administrativo y Financiero Web basado en la arquitectura clásica y reglas de negocio de **mi Software / a2 Administrativo**, modernizado a una interfaz web reactiva (Tailwind CSS + Alpine.js) con backend modular en PHP 8 nativo y base de datos relacional MySQL/MariaDB.

---

## 2. Requisitos del Sistema y Entorno

- **Servidor Web / PHP:** PHP 8.0.0 o superior (compatible con PHP 8.0, 8.1, 8.2, 8.3).
- **Extensiones PHP Requeridas:** `pdo_mysql`, `openssl`, `mbstring`, `curl`, `json` (opcionales: `gd`, `zlib`).
- **Base de Datos:** MySQL 5.7+ o MariaDB 10.3+.
- **XAMPP en Windows:** Ubicación estándar en `C:\xampp`.

---

## 3. Estructura de Directorios del Proyecto

```text
mi/
├── app/
│   ├── Controllers/             # Controladores de vistas y APIs REST
│   │   ├── ActivosFijosController.php
│   │   ├── ApiPreventaController.php
│   │   ├── AprovisionamientoController.php
│   │   ├── ComprasController.php
│   │   ├── ContabilidadController.php
│   │   ├── EcommerceController.php
│   │   ├── FormatosController.php
│   │   ├── InstallerController.php
│   │   ├── InventarioController.php
│   │   ├── MaestrosController.php      # CRUDs de Archivos Maestros
│   │   ├── MigracionController.php
│   │   ├── NominaController.php
│   │   ├── ReportesController.php
│   │   ├── SatController.php
│   │   ├── TelegramWebhookController.php
│   │   ├── TesoreriaController.php
│   │   └── VentasController.php
│   ├── Core/                    # Núcleo del Framework
│   │   ├── Database.php         # Conexión Singleton PDO
│   │   ├── Router.php           # Enrutador dinámico REST
│   │   ├── InstallerService.php # Motor del Asistente de Instalación
│   │   ├── PdfEngine.php        # Generador de documentos e impresión térmica
│   │   ├── WhatsAppService.php  # Integración Meta Cloud API / WhatsApp Web
│   │   ├── TelegramEngine.php   # Notificaciones y Bot Telegram
│   │   └── License/
│   │       └── LicenseValidator.php # Validador Criptográfico RSA-SHA256
│   └── Models/                  # Servicios de Dominio y Lógica de Negocio
│       ├── Producto.php
│       ├── Cliente.php
│       ├── Proveedor.php
│       ├── BancoService.php
│       ├── DocumentoVentaService.php
│       ├── CircuitoComprasService.php
│       ├── NominaService.php
│       ├── ContabilidadService.php
│       ├── ContadorService.php
│       ├── SatService.php
│       ├── SerialesService.php
│       ├── TesoreriaService.php
│       ├── ActivosFijosService.php
│       ├── EcommerceService.php
│       ├── ImportadorMasivoService.php
│       ├── ValuacionInventarioService.php
│       └── ReportesA2Service.php
├── config/
│   ├── database.php             # Credenciales de BD (generado por el wizard)
│   └── keys/
│       ├── license_private_key.pem # Llave privada maestra RSA (2048 bits)
│       └── license_public_key.pem  # Llave pública RSA de validación
├── database/
│   ├── migrations/              # 30 Migraciones SQL secuenciales (001 a 030)
│   └── seeds/                   # Semillas de formatos y catálogos
├── license/
│   └── sistema.lic              # Licencia digital activa emitida con RSA-SHA256
├── public/
│   ├── index.php                # Punto de entrada único del servidor web
│   └── .htaccess                # Reescritura de URLs para Apache
├── storage/
│   ├── backups/                 # Respaldos de base de datos
│   ├── lic/                     # Almacén de licencia instalada
│   └── installed.lock           # Archivo cerrojo de instalación
├── tools/
│   ├── generate_master_keys.php # Generador de llaves maestras RSA
│   └── generate_license.php     # Generador de licencias firmadas (.lic)
├── vendor/
│   └── autoload.php             # Autoloader nativo PSR-4 sin dependencias externas
├── views/                       # Vistas UI (Tailwind CSS + Alpine.js)
│   ├── layout.php               # Layout maestro del sistema
│   ├── installer/wizard.php     # Asistente de instalación inicial en 4 pasos
│   ├── maestros/                # Suite de Archivos Maestros
│   │   ├── panel_maestros.php   # Hub central de catálogos
│   │   ├── productos.php        # Catálogo maestro de productos
│   │   ├── clientes.php         # Ficha maestra de clientes
│   │   ├── proveedores.php      # Ficha maestra de proveedores
│   │   ├── departamentos.php    # Departamentos y categorías
│   │   ├── vendedores.php       # Vendedores y comisiones
│   │   ├── zonas.php            # Zonas y rutas geográficas
│   │   ├── monedas.php          # Monedas y tasas de cambio
│   │   ├── depositos.php        # Depósitos y almacenes
│   │   └── bancos.php           # Bancos, cajas y POS
│   ├── ventas/                  # Punto de Venta POS y facturación
│   ├── compras/                 # Asistente de sugerencia y compras
│   ├── contabilidad/            # Contabilidad integrada y comprobantes
│   ├── contador/                # Panel multicliente del contador
│   ├── nomina/                  # Procesamiento de nómina y recibos
│   ├── sat/                     # Servicio de Asistencia Técnica y taller
│   ├── produccion/              # Fórmulas de ensamble y BOM
│   ├── activos/                 # Activos fijos y depreciaciones
│   ├── reportes/                # Suite de reportes a2
│   ├── ecommerce/               # Tienda web B2C/B2B y checkout bancario
│   ├── preventa/                # Aplicación móvil PWA de preventistas
│   ├── auditoria/               # Bitácora de auditoría y trazabilidad
│   └── configuracion/           # Formatos, migración CSV, WhatsApp, backups
├── iniciar_servidor.bat         # Script para iniciar el servidor local con 1 clic
└── generar_licencia.bat         # Script para emitir licencias con 1 clic
```

---

## 4. Módulos y Funcionalidades Implementadas

### A. Archivos Maestros y Catálogos (`/maestros`)
- **Productos:** Códigos de barra, costos de reposición, precios A, B, C y D calculados automáticamente por margen de utilidad, IVA, stock por almacén, lotes y seriales.
- **Clientes:** RIF/Cédula, límites y días de crédito, retenciones IVA (75%/100%) e ISLR, lista de precio asignada, zona y vendedor.
- **Proveedores:** Tipo de contribuyente, retenciones fiscales, datos bancarios.
- **Departamentos y Categorías:** Árbol jerárquico de inventario.
- **Vendedores:** % de comisiones por facturación y recaudación de cobranzas.
- **Zonas:** Zonificación de clientes y rutas de distribución.
- **Monedas y Tasas:** Manejo dual USD/VES/EUR, actualización de tasa oficial del día e histórico de variaciones.
- **Depósitos:** Ubicaciones de inventario y almacenes.
- **Bancos y Cajas:** Cuentas corrientes, cajas chicas y puntos de venta.

### B. Ventas y Facturación POS (`/pos`)
- Facturación rápida, búsqueda por código de barra, múltiples medios de pago combinados (Divisas, Pago Móvil, Punto de Venta, Efectivo, Crédito), cálculo automático de IGTF y retenciones.
- Generación de tickets térmicos (80mm) y facturas fiscales.

### C. Circuito de Compras en 3 Fases (`/compras/sugerencias`)
1. **Sugerencia Automática de Compra:** Análisis de punto de reorden, stock mínimo y rotación.
2. **Recepción en Almacén:** Entrada física de mercancía y conciliación contra orden de compra.
3. **Facturación CxP:** Registro fiscal de factura del proveedor y actualización de costos de reposición.

### D. Servicio de Asistencia Técnica y Taller (`/sat/panel`)
- Órdenes de trabajo con campos dinámicos (Placas, IMEI, Serial, Kilometraje, Nivel de Combustible).
- Flujo de estados parametrizables (Recibido ➔ Diagnóstico ➔ Presupuestado ➔ Aprobado ➔ En Reparación ➔ Listo ➔ Facturado).

### E. Producción y Ensambles BOM (`/produccion/panel`)
- Fórmulas de producción con costeo de materia prima + mano de obra.
- Descuento automático de insumos al procesar la orden de ensamble y entrada de producto terminado.

### F. Contabilidad Integrada y Panel del Contador (`/contabilidad/panel`, `/contador/multicliente`)
- Plan Único de Cuentas (PUC) a 4 niveles.
- Generación automática de asientos por partida doble desde Ventas, Compras, Pagos, Cobros y Nómina.
- Panel multicliente para despachos contables externos.

### G. Nómina y Gestión de Personal (`/nomina/panel`)
- Ficha de trabajadores, sueldo base, cestaticket.
- Deducciones de ley (IVSS 4%, FAOV 1%, PIE 0.5%) y aportes patronales.
- Asiento contable de nómina y desembolso bancario en un clic.

### H. E-commerce y Pasarelas Bancarias (`/tienda`, `/tienda/checkout`)
- Tienda web con catálogo sincronizado en vivo.
- Validación de pagos automáticos C2P / Pago Móvil mediante API bancaria (Banesco / Banco Plaza).

---

## 5. Procedimiento de Traslado e Instalación en Otra Computadora

Para trasladar y poner en marcha el sistema en una nueva máquina:

### Paso 1: Copiar la Carpeta
Copie toda la carpeta `c:\dasp\mi` a la nueva computadora (por ejemplo, en `C:\dasp\mi` o dentro de `C:\xampp\htdocs\mi`).

### Paso 2: Asegurar Requisitos en la Nueva Máquina
- Instalar **XAMPP** (con PHP 8.0+ y MySQL).
- Iniciar los servicios de **Apache** y **MySQL** desde el Panel de Control de XAMPP.

### Paso 3: Iniciar el Servidor
En la nueva computadora, ejecute:
- Doble clic en `c:\dasp\mi\iniciar_servidor.bat`
- O desde la consola en la carpeta del proyecto:
  ```cmd
  C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public
  ```

### Paso 4: Configurar la Base de Datos (Si es instalación limpia)
1. Abra el navegador en: **`http://localhost:8000/installer`**
2. Verifique los requisitos de servidor (Paso 1).
3. Ingrese credenciales de MySQL (Host: `127.0.0.1`, Usuario: `root`, Contraseña en blanco) y presione **Conectar y Aprovisionar Tablas** (Paso 2).
4. Cargue el archivo de licencia ubicado en `license/sistema.lic` (Paso 3).
5. Cree el usuario Administrador inicial (Paso 4).

### Paso 5: Emitir Nuevas Licencias (Opcional)
Para generar una licencia con el nombre de otra empresa en la nueva máquina:
1. Edite `tools/generate_license.php` con la Razón Social y RIF deseados.
2. Ejecute `generar_licencia.bat`.
3. El archivo `license/sistema.lic` quedará listo para cargarse.

---

*Documento generado para referencia técnica e interoperabilidad del proyecto mi Enterprise Edition.*
