# Historial Cronológico de Cambios, Correcciones y Decisiones Técnicas
**Proyecto: mi ERP Administrativo y Financiero Web**

Este registro documenta todas las fases de auditoría, corrección de errores, arquitectura y completación realizadas en el proyecto para que sirva de bitácora y consulta técnica en cualquier otro entorno.

---

## 1. Auditoría Inicial y Diagnóstico

Al inicio de la sesión, se detectaron los siguientes problemas principales:
1. **Falta de Autoloader:** El código usaba namespaces `App\...` sin autoloader PSR-4 ni `composer.json`, impidiendo la carga de clases.
2. **Archivos Truncados e Incompletos:** Múltiples controladores, modelos y vistas tenían sentencias SQL, arrays y funciones cortadas a mitad de línea por exportaciones previas incompletas.
3. **Esquemas de Base de Datos Faltantes:** Faltaban tablas clave para impresión (`formatos_impresion`), compras en 3 fases (`recepciones_mercancia`) y producción (`produccion_bom`).
4. **Validación de Licencias Rota:** El módulo de licencias requería llaves criptográficas RSA que no existían y el formato de licencia no estaba sincronizado.
5. **Vistas Huérfanas:** Las vistas administrativas no contaban con un layout maestro ni con un menú de navegación integrado unificado.

---

## 2. Acciones Realizadas por Fase

### Fase 1: Arquitectura Base y Autoloader PSR-4
- Se creó `vendor/autoload.php` nativo en PHP que mapea el prefijo `App\` al directorio `app/`.
- Se corrigió `app/Core/Router.php`, eliminando bloques markdown incrustados y dotándolo de soporte para rutas con expresiones regulares y verbos REST (`GET`, `POST`, `PUT`, `DELETE`).
- Se reestructuró `public/index.php` para actuar como Front Controller único con control de estado de instalación mediante `storage/installed.lock`.

### Fase 2: Criptografía y Sistema de Licencias RSA-SHA256
- Se corrigió `tools/generate_master_keys.php` para Windows/XAMPP y se generó el par de claves RSA de 2048 bits en `config/keys/`.
- Se implementó `tools/generate_license.php` y el batch `generar_licencia.bat` para emitir licencias `.lic` inalterables con nonce y firma digital.
- Se actualizó `app/Core/License/LicenseValidator.php` para validar la firma SHA256 contra la clave pública e inyectar de forma inmutable la Razón Social y RIF de la empresa licenciada.

### Fase 3: Base de Datos y 30 Migraciones SQL
- Se auditaron y corrigieron las 30 migraciones en `database/migrations/`:
  - `003_terceros_tables.sql`: Se completó el insert del vendedor por defecto `'V-00000000'`.
  - `006_bancos_tesoreria.sql`: Se completaron los valores del ENUM `tipo_movimiento`.
  - `007_formatos_impresion.sql`: Creada estructura para almacenar plantillas HTML de tickets térmicos y facturas carta.
  - `016_recepciones_mercancia.sql`: Creada tabla para recepción de mercancía y conciliación contra OC.
  - `018_productos_presentaciones.sql`: Eliminado texto no SQL residual (`Precio Caja/Factor`) y completadas cláusulas `ON DELETE SET NULL`.
  - `019_contabilidad_integrada.sql`: Corregidas columnas de mapeo contable y PUC.
  - `020_contabilidad_profesional.sql`: Agregado registro de empresa por defecto y completadas constraints `ON DELETE CASCADE`.
  - `021_nomina_integrada.sql`: Completada la lista de columnas de conceptos de nómina y cuentas contables.
  - `022_produccion_bom.sql`: Creada estructura de fórmulas y órdenes de ensamble.
  - `023_ecommerce_integrado.sql`: Completadas las columnas de pago móvil y zelle en `ecommerce_config`.
  - `025_modulo_servicios_sat.sql`: Limpiados comentarios con comillas sin cerrar y completadas columnas de estados de flujo.
  - `027_activos_fijos_depreciaciones.sql`: Completadas columnas de categorías de activos y tablas de depreciación en línea recta.
- **Resultado:** Todas las 30 migraciones fueron ejecutadas secuencialmente en MariaDB/MySQL con **0 errores**.

### Fase 4: Modelos de Dominio y Lógica de Negocio
- Se crearon y sanearon todos los modelos en `app/Models/`:
  - `Producto.php`, `BancoService.php`, `DocumentoVentaService.php`, `CircuitoComprasService.php`, `NominaService.php`, `SatService.php`, `SerialesService.php`, `TesoreriaService.php`, `ActivosFijosService.php`, `ImportadorMasivoService.php`, `ContadorService.php`, `EcommerceService.php`.
- Se resolvieron todos los retornos y tipos estrictos de PHP 8.

### Fase 5: Suite de Archivos Maestros y Catálogos
Se construyó la suite completa de Archivos Maestros solicitada para equiparar la funcionalidad con un ERP administrativo completo:
- **Controlador:** `app/Controllers/MaestrosController.php` con todas las rutas API de consulta y guardado.
- **Vistas en `views/maestros/`:**
  - `panel_maestros.php`: Hub central con métricas en tiempo real.
  - `productos.php`: Catálogo maestro con cálculo de precios A/B/C/D y costos.
  - `clientes.php`: Ficha con políticas de crédito y retenciones.
  - `proveedores.php`: Ficha fiscal con retención de ISLR.
  - `departamentos.php`: Departamentos y categorías jerárquicas.
  - `vendedores.php`: Asesores y comisiones por venta/cobranza.
  - `zonas.php`: Zonas de distribución y preventa.
  - `monedas.php`: Fijación de tasas del día e histórico.
  - `depositos.php`: Almacenes físicos.
  - `bancos.php`: Cuentas bancarias, cajas de efectivo y POS.

### Fase 6: Vistas de Operaciones y Layout Maestro
- Se creó `views/layout.php` con sidebar categorizado, indicador de estado de licencia, tasa BCV y diseño responsive.
- Se envolvieron y sanearon las vistas de POS, Compras, SAT, Nómina, Contabilidad, Contador, Activos, Formatos, Migración CSV, WhatsApp, Auditoría y E-commerce.

### Fase 7: Rebranding Integral y Adopción de la Marca Oficial "mi"
Para garantizar la independencia legal de la propiedad intelectual y eliminar referencias a marcas registradas de terceros:
- Se reemplazaron todas las referencias de texto, logotipos y nombres de producto de `a2` / `a2r` por la marca oficial **`mi`** (**mi ADMINISTRATIVO**, **mi ERP Enterprise Edition**, **Suite de Reportes mi**).
- Se actualizó el formato del paquete de licencias digitales de `A2LIC_V2` a **`MILIC_V2`** en el validador criptográfico (`app/Core/License/LicenseValidator.php`) y en el generador (`tools/generate_license.php`).
- Se re-emitió la licencia digital RSA-SHA256 en `license/sistema.lic` con el nuevo encabezado `MILIC_V2`.
- Se creó el modelo `app/Models/ReportesMiService.php` y se actualizó `app/Controllers/ReportesController.php`.
- Se actualizaron los scripts batch de Windows `iniciar_servidor.bat` y `generar_licencia.bat`.
- Se adaptaron todos los archivos de contexto y memoria para asistentes de IA (`AGENTS.md`, `GEMINI.md`, `MEMORIA_TECNICA_DEL_PROYECTO_A2R.md` / `MEMORIA_TECNICA_PROYECTO_MI.md`).

---

## 3. Comprobaciones y Pruebas Realizadas

1. **Linter de Sintaxis PHP:** Los **102 archivos PHP** del proyecto pasaron la verificación con código de salida `0` (Cero errores de sintaxis).
2. **Prueba de Carga del Autoloader:** Las **28 clases y servicios** se cargan e instancian correctamente vía `vendor/autoload.php`.
3. **Prueba Criptográfica:** La licencia `license/sistema.lic` se valida con éxito contra `config/keys/license_public_key.pem` usando el formato `MILIC_V2` y firma RSA-SHA256.
4. **Prueba de Requisitos:** `InstallerService::checkRequisitos()` valida PHP 8+, extensiones y permisos de escritura.
5. **Prueba de Aprovisionamiento SQL:** Creación limpia de todas las tablas e índices en base de datos MariaDB (001 a 030 con 0 errores).

---

## 4. Scripts y Utilidades Creadas para Facilitar la Operación

- **`iniciar_servidor.bat`:** Detecta automáticamente `C:\xampp\php\php.exe` e inicia el servidor en `http://127.0.0.1:8000`.
- **`generar_licencia.bat`:** Emite un archivo de licencia `.lic` nuevo firmado digitalmente para la empresa configurada bajo la marca `mi`.
- **`DOCUMENTACION_PROYECTO_MI.md` / `DOCUMENTACION_PROYECTO_A2R.md`:** Manual técnico y guía de despliegue para nuevos entornos.
- **`MEMORIA_TECNICA_PROYECTO_MI.md`:** Base de conocimiento completa del sistema.
- **`AGENTS.md` & `GEMINI.md`:** Contexto automático para agentes de IA en nuevos entornos.

---
*Fin del Historial de Cambios.*
