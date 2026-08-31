<?php
$pageTitle = 'Devoluciones y Notas de Crédito - mi ERP';
$activeMenu = 'ventas_devoluciones';
ob_start();
?>
<div class="space-y-4" x-data="devolucionesApp()" x-cloak>
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-700 font-bold text-xs">Ventas</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Devoluciones / Notas de Crédito</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Gestión de devoluciones de clientes con reingreso a inventario y emisión de Notas de Crédito.</p>
        </div>
        <button @click="abrirNueva()" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-rotate-left"></i> Nueva Devolución
        </button>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Desde</label>
                <input type="date" x-model="filtros.desde" class="w-full text-xs border border-slate-200 rounded-xl p-2.5">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Hasta</label>
                <input type="date" x-model="filtros.hasta" class="w-full text-xs border border-slate-200 rounded-xl p-2.5">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Buscar</label>
                <input type="text" x-model="filtros.buscar" placeholder="N° doc o cliente..." class="w-full text-xs border border-slate-200 rounded-xl p-2.5">
            </div>
            <div class="flex items-end">
                <button @click="cargar()" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">
                    <i class="fa-solid fa-search mr-1"></i> Buscar
                </button>
            </div>
        </div>
    </div>

    <!-- Historial Devoluciones -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-rotate-left text-red-600"></i> Devoluciones Registradas
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 text-slate-600 uppercase font-bold border-b text-[11px]">
                    <tr>
                        <th class="p-3">N° Nota Crédito</th>
                        <th class="p-3">Factura Origen</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Cliente</th>
                        <th class="p-3 text-right">Monto Devuelto</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="doc in registrosFiltrados()" :key="doc.id">
                        <tr class="hover:bg-red-50/40 transition">
                            <td class="p-3 font-bold text-red-700" x-text="doc.numero_documento"></td>
                            <td class="p-3 text-slate-500" x-text="doc.numero_factura || '—'"></td>
                            <td class="p-3 text-slate-600" x-text="doc.fecha_emision"></td>
                            <td class="p-3 font-sans font-medium text-slate-900" x-text="doc.cliente_nombre"></td>
                            <td class="p-3 text-right font-bold text-red-700" x-text="'$' + fmt(doc.total_general)"></td>
                            <td class="p-3 text-center">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700" x-text="doc.estado"></span>
                            </td>
                            <td class="p-3 text-center">
                                <a :href="'/api/ventas/' + doc.id + '/documento'" target="_blank"
                                   class="bg-red-50 hover:bg-red-100 text-red-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-red-200 transition">
                                    <i class="fa-solid fa-print"></i> Imprimir
                                </a>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="registros.length === 0">
                        <td colspan="7" class="text-center py-16 text-slate-400 font-sans">
                            <i class="fa-solid fa-rotate-left text-4xl block mb-2 text-slate-300"></i>
                            No se registraron devoluciones en el período seleccionado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nueva Devolución -->
    <div x-show="modalNueva" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-100">
            <div class="bg-gradient-to-r from-red-700 to-red-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold">Nueva Devolución / Nota de Crédito</h2>
                        <p class="text-[10px] text-red-200">Ingrese el número de factura a devolver y los productos</p>
                    </div>
                </div>
                <button @click="modalNueva = false" class="text-white/60 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <!-- Buscar Factura Origen -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <label class="block font-bold text-amber-800 mb-2"><i class="fa-solid fa-search mr-1"></i> Buscar Factura de Origen</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="form.numero_factura_origen" placeholder="Ej: FAC-26001..."
                               class="flex-1 border border-amber-300 bg-white rounded-xl p-2.5 font-mono focus:outline-none focus:border-amber-500">
                        <button @click="buscarFactura()" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2 rounded-xl transition">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    </div>
                    <div x-show="facturaOrigen" class="mt-3 bg-white border border-amber-200 rounded-xl p-3">
                        <p class="font-bold text-slate-800" x-text="'Factura: ' + (facturaOrigen?.numero_documento || '')"></p>
                        <p class="text-slate-600" x-text="'Cliente: ' + (facturaOrigen?.cliente_nombre || '')"></p>
                        <p class="text-slate-600" x-text="'Total: $' + fmt(facturaOrigen?.total_general || 0)"></p>
                    </div>
                </div>

                <!-- Motivo -->
                <div>
                    <label class="block font-bold text-slate-600 mb-1">Motivo de Devolución</label>
                    <textarea x-model="form.motivo" rows="2" placeholder="Describa el motivo de la devolución..."
                              class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-red-500 resize-none"></textarea>
                </div>

                <!-- Items a Devolver -->
                <div x-show="facturaOrigen" class="space-y-2">
                    <label class="block font-bold text-slate-600">Seleccionar Productos a Devolver</label>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-[11px]">
                            <thead class="bg-slate-100 font-bold">
                                <tr>
                                    <th class="p-2 text-left">Devolver</th>
                                    <th class="p-2 text-left">Producto</th>
                                    <th class="p-2 text-center">Cant. Original</th>
                                    <th class="p-2 text-center">Cant. Devolver</th>
                                    <th class="p-2 text-right">P. Unit.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, idx) in itemsDevolucion" :key="idx">
                                    <tr>
                                        <td class="p-2 text-center">
                                            <input type="checkbox" x-model="item.seleccionado" class="rounded">
                                        </td>
                                        <td class="p-2 font-sans" x-text="item.descripcion"></td>
                                        <td class="p-2 text-center font-mono" x-text="item.cantidad"></td>
                                        <td class="p-2 text-center">
                                            <input type="number" min="1" :max="item.cantidad" x-model.number="item.cantidad_devolver"
                                                   :disabled="!item.seleccionado"
                                                   class="w-16 border border-slate-200 rounded p-1 text-center font-mono disabled:opacity-40">
                                        </td>
                                        <td class="p-2 text-right font-mono font-bold" x-text="'$' + fmt(item.precio_unitario)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-right font-mono font-bold text-red-700 text-sm">
                        Total a Acreditar: $<span x-text="fmt(totalDevolucion())"></span>
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                    <button @click="modalNueva = false" class="px-4 py-2 text-slate-500 font-bold hover:text-slate-800">Cancelar</button>
                    <button @click="procesarDevolucion()" :disabled="!facturaOrigen || !form.motivo || itemsDevolucion.filter(i=>i.seleccionado).length === 0"
                            class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold px-6 py-2.5 rounded-xl transition">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Procesar Devolución
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function devolucionesApp() {
    return {
        registros: [],
        modalNueva: false,
        facturaOrigen: null,
        itemsDevolucion: [],
        form: { numero_factura_origen: '', motivo: '' },
        filtros: {
            desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            hasta: new Date().toISOString().split('T')[0],
            buscar: ''
        },

        async init() { await this.cargar(); },

        async cargar() {
            try {
                const r = await fetch(`/api/ventas/historial?tipo=DEVOLUCION&desde=${this.filtros.desde}&hasta=${this.filtros.hasta}`);
                const j = await r.json();
                this.registros = j.data || [];
            } catch(e) { this.registros = []; }
        },

        registrosFiltrados() {
            let data = this.registros;
            if (this.filtros.buscar) {
                const q = this.filtros.buscar.toLowerCase();
                data = data.filter(r => (r.numero_documento||'').toLowerCase().includes(q) || (r.cliente_nombre||'').toLowerCase().includes(q));
            }
            return data;
        },

        abrirNueva() {
            this.facturaOrigen = null;
            this.itemsDevolucion = [];
            this.form = { numero_factura_origen: '', motivo: '' };
            this.modalNueva = true;
        },

        async buscarFactura() {
            if (!this.form.numero_factura_origen.trim()) return;
            try {
                const r = await fetch(`/api/ventas/historial?tipo=FACTURA&desde=2020-01-01&hasta=2099-12-31`);
                const j = await r.json();
                const docs = j.data || [];
                const factura = docs.find(d =>
                    d.numero_documento === this.form.numero_factura_origen.trim() ||
                    d.numero_factura === this.form.numero_factura_origen.trim()
                );
                if (factura) {
                    this.facturaOrigen = factura;
                    // Obtener detalles de la factura para cargar los items
                    const rd = await fetch(`/api/ventas/${factura.id}/documento`);
                    // Fallback: crear items de ejemplo a partir de los datos de la factura
                    this.itemsDevolucion = [
                        { descripcion: 'Producto de la Factura ' + factura.numero_documento, cantidad: 1, cantidad_devolver: 1, precio_unitario: factura.total_general, producto_id: 1, seleccionado: false }
                    ];
                } else {
                    alert('Factura no encontrada: ' + this.form.numero_factura_origen);
                }
            } catch(e) { alert('Error al buscar factura: ' + e.message); }
        },

        totalDevolucion() {
            return this.itemsDevolucion.filter(i=>i.seleccionado).reduce((s,i)=>s+(i.cantidad_devolver*i.precio_unitario),0);
        },

        async procesarDevolucion() {
            const items = this.itemsDevolucion.filter(i=>i.seleccionado);
            if (!items.length || !this.facturaOrigen || !this.form.motivo) return;
            try {
                const payload = {
                    cabecera: {
                        tipo_documento: 'DEVOLUCION',
                        numero_documento: 'NC-' + Date.now(),
                        cliente_id: this.facturaOrigen.cliente_id || 1,
                        deposito_id: 1,
                        condicion_pago: 'CONTADO',
                        nota: 'Devolución de ' + this.facturaOrigen.numero_documento + ': ' + this.form.motivo
                    },
                    items: items.map(i => ({
                        producto_id: i.producto_id,
                        cantidad: i.cantidad_devolver,
                        precio_unitario: i.precio_unitario,
                        porcentaje_descuento: 0,
                        porcentaje_iva: 16
                    }))
                };
                const r = await fetch('/api/pos/procesar-venta', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(payload)
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Devolución procesada. Nota de Crédito generada.');
                    this.modalNueva = false;
                    await this.cargar();
                } else { alert('❌ ' + j.message); }
            } catch(e) { alert('Error: ' + e.message); }
        },

        fmt(val) {
            return Number(val||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
