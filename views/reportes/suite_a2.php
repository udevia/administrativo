<?php
$pageTitle = 'Suite Completa de Reportes - mi';
$activeMenu = 'reportes';
ob_start();
?>
<div class="space-y-4" x-data="suiteReportesApp()">
    <!-- Barra Superior -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-chart-column text-blue-600"></i> Suite Completa de Reportes
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Catálogo homologado de auditoría, comercialización, finanzas e inventario mi ERP</p>
        </div>
        <!-- Filtros Globales -->
        <div class="flex items-center gap-2">
            <input type="date" x-model="filtros.desde" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-mono">
            <input type="date" x-model="filtros.hasta" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-mono">
            <button @click="cargarReporte()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass"></i> Consultar
            </button>
            <button @click="imprimirReporte()" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Imprimir / PDF
            </button>
        </div>
    </div>

    <!-- Layout Dividido: Árbol de Reportes a la Izquierda, Resultados a la Derecha -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- Panel Izquierdo: Menú Árbol de Reportes estilo a2 (3 cols) -->
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-col space-y-3">
            <h3 class="font-bold text-xs uppercase text-slate-400 tracking-wider">Módulos de Reporte</h3>
            
            <div class="space-y-1 text-xs">
                <!-- Ventas -->
                <div class="font-bold text-slate-700 py-1.5 px-2 bg-slate-100 rounded-lg flex items-center gap-2 mt-1">
                    <i class="fa-solid fa-cash-register text-blue-500"></i> Ventas y Comercial
                </div>
                <button @click="seleccionarReporte('ventas-clientes', 'Ventas por Cliente')" :class="reporteActivo === 'ventas-clientes' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Ventas por Cliente</button>
                <button @click="seleccionarReporte('ventas-articulos', 'Ventas por Artículo')" :class="reporteActivo === 'ventas-articulos' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Ventas por Artículo</button>
                <button @click="seleccionarReporte('ventas-vendedores', 'Ventas por Vendedor')" :class="reporteActivo === 'ventas-vendedores' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Ventas por Vendedor</button>
                <button @click="seleccionarReporte('rentabilidad', 'Rentabilidad por Artículo')" :class="reporteActivo === 'rentabilidad' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Rentabilidad por Artículo</button>
                <button @click="seleccionarReporte('documentos-anulados', 'Documentos Anulados')" :class="reporteActivo === 'documentos-anulados' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Documentos Anulados</button>

                <!-- Compras -->
                <div class="font-bold text-slate-700 py-1.5 px-2 bg-slate-100 rounded-lg flex items-center gap-2 mt-3">
                    <i class="fa-solid fa-truck-ramp-box text-emerald-500"></i> Compras y Gastos
                </div>
                <button @click="seleccionarReporte('compras-proveedores', 'Compras por Proveedor')" :class="reporteActivo === 'compras-proveedores' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Compras por Proveedor</button>
                <button @click="seleccionarReporte('ordenes-pendientes', 'Órdenes de Compra Pendientes')" :class="reporteActivo === 'ordenes-pendientes' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Órdenes Pendientes</button>
                <button @click="seleccionarReporte('retenciones-acumuladas', 'Retenciones IVA / ISLR')" :class="reporteActivo === 'retenciones-acumuladas' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Retenciones IVA / ISLR</button>

                <!-- Inventario -->
                <div class="font-bold text-slate-700 py-1.5 px-2 bg-slate-100 rounded-lg flex items-center gap-2 mt-3">
                    <i class="fa-solid fa-boxes-stacked text-amber-500"></i> Inventario y Depósitos
                </div>
                <button @click="seleccionarReporte('stock-depositos', 'Stock por Depósito')" :class="reporteActivo === 'stock-depositos' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Stock por Depósito</button>
                <button @click="seleccionarReporte('inventario-valorizado', 'Inventario Valorizado')" :class="reporteActivo === 'inventario-valorizado' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Inventario Valorizado</button>
                <button @click="seleccionarReporte('stock-minimo', 'Artículos Bajo Mínimo')" :class="reporteActivo === 'stock-minimo' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Artículos Bajo Mínimo</button>
                <button @click="seleccionarReporte('lotes-vencimiento', 'Lotes Próximos a Vencer')" :class="reporteActivo === 'lotes-vencimiento' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Lotes Próximos a Vencer</button>

                <!-- Finanzas -->
                <div class="font-bold text-slate-700 py-1.5 px-2 bg-slate-100 rounded-lg flex items-center gap-2 mt-3">
                    <i class="fa-solid fa-scale-balanced text-indigo-500"></i> CxC, CxP y Fiscal
                </div>
                <button @click="seleccionarReporte('cxc-antiguedad', 'Antigüedad de Saldos CxC')" :class="reporteActivo === 'cxc-antiguedad' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Antigüedad Saldos CxC</button>
                <button @click="seleccionarReporte('cxp-antiguedad', 'Antigüedad de Saldos CxP')" :class="reporteActivo === 'cxp-antiguedad' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Antigüedad Saldos CxP</button>
                <button @click="seleccionarReporte('cxc-cobranzas', 'Relación de Cobranzas')" :class="reporteActivo === 'cxc-cobranzas' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Relación de Cobranzas</button>
                <button @click="seleccionarReporte('fiscal-correlativos', 'Auditoría Fiscal de Correlativos')" :class="reporteActivo === 'fiscal-correlativos' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left py-1.5 px-3 rounded-lg transition">Auditoría Fiscal Correlativos</button>
            </div>
        </div>

        <!-- Panel Derecho: Vista Previa y Datos (9 cols) -->
        <div class="lg:col-span-9 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col">
            <div class="border-b pb-3 mb-3 flex justify-between items-center">
                <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider" x-text="tituloReporte"></h3>
                <span class="text-xs text-slate-500 font-mono" x-text="'Registros: ' + registros.length"></span>
            </div>
            <div class="overflow-x-auto min-h-[400px]" id="areaImpresion">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-100 text-slate-700 uppercase font-bold border-b">
                        <tr>
                            <template x-for="col in columnas" :key="col.key">
                                <th class="p-2.5" :class="col.align === 'right' ? 'text-right' : (col.align === 'center' ? 'text-center' : '')" x-text="col.label"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        <template x-for="(fila, idx) in registros" :key="idx">
                            <tr class="hover:bg-blue-50/40 transition">
                                <template x-for="col in columnas" :key="col.key">
                                    <td class="p-2.5" :class="[col.align === 'right' ? 'text-right' : (col.align === 'center' ? 'text-center' : ''), col.fontSans ? 'font-sans font-medium' : '']" x-text="formatCol(fila[col.key], col.type)"></td>
                                </template>
                            </tr>
                        </template>
                        <tr x-show="registros.length === 0">
                            <td :colspan="columnas.length || 5" class="text-center py-16 text-slate-400 font-sans">
                                No se encontraron datos para los filtros seleccionados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function suiteReportesApp() {
    return {
        reporteActivo: 'ventas-clientes',
        tituloReporte: 'Ventas por Cliente',
        filtros: {
            desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            hasta: new Date().toISOString().split('T')[0]
        },
        columnas: [],
        registros: [],
        init() {
            this.configurarColumnas();
            this.cargarReporte();
        },
        seleccionarReporte(rep, tit) {
            this.reporteActivo = rep;
            this.tituloReporte = tit;
            this.configurarColumnas();
            this.cargarReporte();
        },
        configurarColumnas() {
            const esquemas = {
                'ventas-clientes': [
                    { key: 'codigo', label: 'Código' },
                    { key: 'razon_social', label: 'Cliente', fontSans: true },
                    { key: 'documento_fiscal', label: 'RIF/CI' },
                    { key: 'total_documentos', label: 'Facturas', align: 'center' },
                    { key: 'subtotal', label: 'Subtotal', align: 'right', type: 'money' },
                    { key: 'iva', label: 'IVA', align: 'right', type: 'money' },
                    { key: 'total', label: 'Total $', align: 'right', type: 'money' }
                ],
                'ventas-articulos': [
                    { key: 'codigo', label: 'Código' },
                    { key: 'descripcion', label: 'Descripción del Producto', fontSans: true },
                    { key: 'unidades_vendidas', label: 'Cant. Vendida', align: 'center' },
                    { key: 'total_neto', label: 'Total Neto $', align: 'right', type: 'money' },
                    { key: 'total_bruto', label: 'Total Bruto $', align: 'right', type: 'money' }
                ],
                'stock-depositos': [
                    { key: 'deposito', label: 'Depósito', fontSans: true },
                    { key: 'producto_codigo', label: 'Código' },
                    { key: 'producto', label: 'Producto', fontSans: true },
                    { key: 'existencia', label: 'Stock Real', align: 'center' },
                    { key: 'existencia_comprometida', label: 'Comprometido', align: 'center' },
                    { key: 'disponible', label: 'Disponible', align: 'center' },
                    { key: 'costo_promedio', label: 'Costo Prom.', align: 'right', type: 'money' },
                    { key: 'valor_total', label: 'Valor Total $', align: 'right', type: 'money' }
                ],
                'lotes-vencimiento': [
                    { key: 'codigo', label: 'Código' },
                    { key: 'descripcion', label: 'Producto', fontSans: true },
                    { key: 'numero_lote', label: 'Lote' },
                    { key: 'fecha_vencimiento', label: 'Vencimiento', align: 'center' },
                    { key: 'existencia', label: 'Stock', align: 'center' },
                    { key: 'dias_restantes', label: 'Días Restantes', align: 'center' }
                ],
                'cxc-antiguedad': [
                    { key: 'codigo', label: 'Código' },
                    { key: 'razon_social', label: 'Cliente', fontSans: true },
                    { key: 'saldo_actual', label: 'Saldo Total $', align: 'right', type: 'money' },
                    { key: 'por_vencer', label: 'Por Vencer', align: 'right', type: 'money' },
                    { key: 'vencido_1_15', label: '1-15 Días', align: 'right', type: 'money' },
                    { key: 'vencido_16_30', label: '16-30 Días', align: 'right', type: 'money' },
                    { key: 'vencido_mas_30', label: '+30 Días', align: 'right', type: 'money' }
                ],
                'cxp-antiguedad': [
                    { key: 'codigo', label: 'Código' },
                    { key: 'razon_social', label: 'Proveedor', fontSans: true },
                    { key: 'saldo_actual', label: 'Saldo Total $', align: 'right', type: 'money' },
                    { key: 'por_vencer', label: 'Por Vencer', align: 'right', type: 'money' },
                    { key: 'vencido_1_15', label: '1-15 Días', align: 'right', type: 'money' },
                    { key: 'vencido_16_30', label: '16-30 Días', align: 'right', type: 'money' },
                    { key: 'vencido_mas_30', label: '+30 Días', align: 'right', type: 'money' }
                ]
            };
            this.columnas = esquemas[this.reporteActivo] || [
                { key: 'codigo', label: 'Código' },
                { key: 'descripcion', label: 'Descripción', fontSans: true },
                { key: 'total', label: 'Total', align: 'right', type: 'money' }
            ];
        },
        async cargarReporte() {
            try {
                const res = await fetch(`/api/reportes/${this.reporteActivo}?desde=${this.filtros.desde}&hasta=${this.filtros.hasta}`);
                const json = await res.json();
                this.registros = json.data || [];
            } catch(e) {
                this.registros = [];
            }
        },
        formatCol(val, type) {
            if (val === null || val === undefined) return '-';
            if (type === 'money') {
                return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            return val;
        },
        imprimirReporte() {
            window.print();
        }
    }
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
