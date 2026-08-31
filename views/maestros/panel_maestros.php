<?php
$pageTitle = 'Archivos Maestros y Catálogos - mi';
$activeMenu = 'maestros';
ob_start();
?>
<div class="space-y-6" x-data="panelMaestrosHubApp()">
    <!-- Encabezado Principal -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white rounded-3xl p-6 shadow-xl border border-slate-700/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30 text-[11px] font-bold uppercase tracking-wider">Centro de Catálogos</span>
                <h1 class="text-2xl font-black tracking-tight">Archivos Maestros del Sistema</h1>
            </div>
            <p class="text-xs text-slate-300">Base estructural del ERP: inventario, terceros, clasificación, logística, finanzas y tesorería.</p>
        </div>
        <button @click="cargarResumen()" class="bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition border border-slate-600 flex items-center gap-2 self-start md:self-auto">
            <i class="fa-solid fa-arrows-rotate" :class="cargando ? 'fa-spin' : ''"></i>
            <span>Actualizar Métricas</span>
        </button>
    </div>

    <!-- Grid de Tarjetas Maestras -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- 1. Catálogo de Productos -->
        <a href="/maestros/productos" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold group-hover:bg-blue-600 group-hover:text-white transition">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_productos || '0'"></span>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-blue-600 transition">Productos e Inventario</h2>
                <p class="text-xs text-slate-500 mt-1">Precios A/B/C/D, costos de reposición, existencias por depósito, seriales y códigos de barra.</p>
            </div>
            <div class="text-xs font-bold text-blue-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Gestionar Catálogo</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 2. Ficha de Clientes -->
        <a href="/maestros/clientes" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-emerald-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold group-hover:bg-emerald-600 group-hover:text-white transition">
                    <i class="fa-solid fa-address-book"></i>
                </div>
                <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_clientes || '0'"></span>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-emerald-600 transition">Ficha de Clientes</h2>
                <p class="text-xs text-slate-500 mt-1">Límites de crédito en USD, días de crédito, retenciones IVA/ISLR, lista de precio default y zonas.</p>
            </div>
            <div class="text-xs font-bold text-emerald-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Administrar Clientes</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 3. Ficha de Proveedores -->
        <a href="/maestros/proveedores" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold group-hover:bg-indigo-600 group-hover:text-white transition">
                    <i class="fa-solid fa-truck-field"></i>
                </div>
                <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_proveedores || '0'"></span>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-indigo-600 transition">Proveedores Comerciales</h2>
                <p class="text-xs text-slate-500 mt-1">Contactos, RIF, tipo de contribuyente fiscal y porcentaje de retención de ISLR.</p>
            </div>
            <div class="text-xs font-bold text-indigo-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Administrar Proveedores</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 4. Departamentos y Categorías -->
        <a href="/maestros/departamentos" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-purple-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl font-bold group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fa-solid fa-sitemap"></i>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_departamentos || '0'"></span>
                    <span class="text-[10px] text-slate-400 block font-mono" x-text="'(' + (resumen.total_categorias || 0) + ' Categorías)'"></span>
                </div>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-purple-600 transition">Deptos y Categorías</h2>
                <p class="text-xs text-slate-500 mt-1">Estructura de agrupación jerárquica de inventario para reportes y estadísticas.</p>
            </div>
            <div class="text-xs font-bold text-purple-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Gestionar Jerarquía</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 5. Vendedores y Comisiones -->
        <a href="/maestros/vendedores" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-cyan-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl font-bold group-hover:bg-cyan-600 group-hover:text-white transition">
                    <i class="fa-solid fa-user-tag"></i>
                </div>
                <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_vendedores || '0'"></span>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-cyan-600 transition">Vendedores & Comisiones</h2>
                <p class="text-xs text-slate-500 mt-1">Fuerza de ventas, comisiones asignadas por ventas brutas y cobros efectivamente recaudados.</p>
            </div>
            <div class="text-xs font-bold text-cyan-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Gestionar Vendedores</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 6. Zonas y Rutas -->
        <a href="/maestros/zonas" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-teal-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold group-hover:bg-teal-600 group-hover:text-white transition">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_zonas || '0'"></span>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-teal-600 transition">Zonas y Rutas de Despacho</h2>
                <p class="text-xs text-slate-500 mt-1">División geográfica para asignación de preventistas, transportistas y rutas de entrega.</p>
            </div>
            <div class="text-xs font-bold text-teal-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Gestionar Zonas</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 7. Monedas y Tasas -->
        <a href="/maestros/monedas" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-amber-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold group-hover:bg-amber-600 group-hover:text-white transition">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_monedas || '2'"></span>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-amber-600 transition">Monedas & Tasas Oficiales</h2>
                <p class="text-xs text-slate-500 mt-1">Configuración multimoneda (USD/VES/EUR), fijación de tasa del día e histórico.</p>
            </div>
            <div class="text-xs font-bold text-amber-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Gestionar Tasas</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 8. Depósitos / Almacenes -->
        <a href="/maestros/depositos" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-orange-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl font-bold group-hover:bg-orange-600 group-hover:text-white transition">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
                <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_depositos || '1'"></span>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-orange-600 transition">Depósitos y Almacenes</h2>
                <p class="text-xs text-slate-500 mt-1">Ubicaciones físicas de resguardo de existencias, tiendas y transferencias internas.</p>
            </div>
            <div class="text-xs font-bold text-orange-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Gestionar Depósitos</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- 9. Bancos y Cajas -->
        <a href="/maestros/bancos" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-rose-300 transition group flex flex-col justify-between space-y-4">
            <div class="flex justify-between items-start">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold group-hover:bg-rose-600 group-hover:text-white transition">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <span class="text-2xl font-black font-mono text-slate-800" x-text="resumen.total_cuentas_banco || '1'"></span>
            </div>
            <div>
                <h2 class="font-bold text-slate-800 text-base group-hover:text-rose-600 transition">Bancos, Cajas & POS</h2>
                <p class="text-xs text-slate-500 mt-1">Cuentas bancarias en divisas y bolívares, cajas de efectivo y terminales de cobro.</p>
            </div>
            <div class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-2 border-t border-slate-100">
                <span>Gestionar Tesorería</span>
                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
            </div>
        </a>
    </div>
</div>

<script>
function panelMaestrosHubApp() {
    return {
        resumen: {},
        cargando: false,
        async init() {
            await this.cargarResumen();
        },
        async cargarResumen() {
            this.cargando = true;
            try {
                const res = await fetch('/api/maestros/resumen');
                const json = await res.json();
                if (json.status === 'success') {
                    this.resumen = json.data || {};
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.cargando = false;
            }
        }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
