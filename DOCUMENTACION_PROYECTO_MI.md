# Guía Completa de Referencia y Documentación Técnica del Sistema (mi ERP)
**Sistema Administrativo y Financiero Web - Enterprise Edition**

> **Nota para migración a otro equipo:** Al copiar la carpeta del proyecto a otra computadora con PHP 8.0+ y MySQL/MariaDB (XAMPP), este documento contiene todos los detalles necesarios para su ejecución inmediata.

---

## 1. Resumen Ejecutivo del Proyecto

El sistema **mi ERP / mi Administrativo** es un software de gestión empresarial y financiero web moderno con interfaz reactiva (Tailwind CSS + Alpine.js), backend modular en PHP 8 nativo y base de datos relacional MySQL/MariaDB.

---

## 2. Requisitos del Sistema y Entorno

- **Servidor Web / PHP:** PHP 8.0.0 o superior (compatible con PHP 8.0, 8.1, 8.2, 8.3).
- **Extensiones PHP Requeridas:** `pdo_mysql`, `openssl`, `mbstring`, `curl`, `json` (opcionales: `gd`, `zlib`).
- **Base de Datos:** MySQL 5.7+ o MariaDB 10.3+.
- **XAMPP en Windows:** Ubicación estándar en `C:\xampp`.

---

## 3. Estructura de Directorios del Proyecto

```text
a2r/
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
│   │       └── LicenseValidator.php # Validador Criptográfico RSA-SHA256 (MILIC_V2)
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
│       └── ReportesMiService.php
├── config/
│   ├── database.php             # Credenciales de BD (generado por el wizard)
│   └── keys/
│       ├── license_private_key.pem # Llave privada maestra RSA (2048 bits)
│       └── license_public_key.pem  # Llave pública RSA de validación
├── database/
│   ├── migrations/              # 30 Migraciones SQL secuenciales (001 a 030)
│   └── seeds/                   # Semillas de formatos y catálogos
├── license/
│   └── sistema.lic              # Licencia digital activa emitida con RSA-SHA256 (MILIC_V2)
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
│   ├── layout.php               # Layout maestro del sistema (Marca mi)
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
│   ├── reportes/                # Suite de reportes mi ERP
│   ├── ecommerce/               # Tienda web B2C/B2B y checkout bancario
│   ├── preventa/                # Aplicación móvil PWA de preventistas
│   ├── auditoria/               # Bitácora de auditoría y trazabilidad
│   └── configuracion/           # Formatos, migración CSV, WhatsApp, backups
├── iniciar_servidor.bat         # Script para iniciar el servidor local con 1 clic
└── generar_licencia.bat         # Script para emitir licencias con 1 clic
```

---

## 4. Procedimiento de Traslado e Instalación en Otra Computadora

Para trasladar y poner en marcha el sistema en una nueva máquina:

### Paso 1: Copiar la Carpeta
Copie toda la carpeta del proyecto a la nueva computadora (por ejemplo, en `C:\dasp\a2r` o `C:\xampp\htdocs\mi_erp`).

### Paso 2: Iniciar Servicios en XAMPP
- Abrir el Panel de Control de XAMPP.
- Iniciar los módulos **Apache** y **MySQL**.

### Paso 3: Iniciar el Servidor de Desarrollo
Haga doble clic en **`iniciar_servidor.bat`** en la raíz del proyecto. El servidor se iniciará en `http://127.0.0.1:8000`.

### Paso 4: Setup Inicial (Si es instalación limpia)
1. Ingrese a: **`http://localhost:8000/installer`**.
2. Siga los 4 pasos del asistente (Requisitos, Conexión BD, Licencia y Usuario Admin).
3. Use el archivo de licencia **`license/sistema.lic`**.

---
*Documento generado para referencia técnica del proyecto mi ERP.*
