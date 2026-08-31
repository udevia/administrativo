# Instrucciones del Proyecto para Agentes de IA (mi ERP)

Este repositorio contiene el sistema **mi ERP / mi Administrativo** (ERP Administrativo y Financiero Web). Al interactuar con este proyecto en cualquier equipo o entorno, debes seguir estrictamente los siguientes principios y contexto:

## 1. Reglas de Arquitectura
- **Marca Oficial:** **`mi`** (**mi ADMINISTRATIVO**, **mi ERP Enterprise Edition**, **Suite de Reportes mi**). No utilizar marcas registradas de terceros.
- **Backend:** PHP 8.0+ sin Composer ni frameworks externos. Todas las clases usan el namespace `App\` y se cargan mediante `vendor/autoload.php`.
- **Frontend:** Vistas en `views/` con Tailwind CSS (vía CDN) y Alpine.js para reactividad.
- **Layout:** Todas las vistas principales se envuelven en `views/layout.php` usando `$pageTitle`, `$activeMenu` y `$slot = ob_get_clean()`.
- **Enrutamiento:** Front Controller único en `public/index.php` gestionado por `App\Core\Router`.
- **Base de Datos:** MySQL / MariaDB mediante `App\Core\Database::getInstance()->getConnection()`.

## 2. Documentos de Referencia Clave en el Repositorio
- Para conocer la memoria completa, diseño y fórmulas de negocio: [`MEMORIA_TECNICA_PROYECTO_MI.md`](file:///MEMORIA_TECNICA_PROYECTO_MI.md) o [`MEMORIA_TECNICA_DEL_PROYECTO_A2R.md`](file:///MEMORIA_TECNICA_DEL_PROYECTO_A2R.md)
- Para la guía de despliegue y puesta en marcha: [`DOCUMENTACION_PROYECTO_MI.md`](file:///DOCUMENTACION_PROYECTO_MI.md) o [`DOCUMENTACION_PROYECTO_A2R.md`](file:///DOCUMENTACION_PROYECTO_A2R.md)
- Para el historial de correcciones y cambios: [`HISTORIAL_CONVERSACION_Y_CAMBIOS.md`](file:///HISTORIAL_CONVERSACION_Y_CAMBIOS.md)

## 3. Criptografía y Licencias
- Las licencias son archivos JSON firmados con RSA-SHA256 (`license/sistema.lic`) con cabecera `MILIC_V2`.
- La clave pública está en `config/keys/license_public_key.pem`.
- Para emitir una nueva licencia se usa `tools/generate_license.php` (o `generar_licencia.bat`).

## 4. Archivos Maestros y Módulos Principales
- Los módulos maestros residen en `views/maestros/` y su API en `App\Controllers\MaestrosController`.
- Módulos operativos: `/pos` (Ventas), `/compras/sugerencias` (Compras), `/sat/panel` (Taller), `/produccion/panel` (BOM), `/contabilidad/panel` (Contabilidad), `/nomina/panel` (Nómina), `/activos/panel` (Activos Fijos), `/maestros` (Catálogos).
