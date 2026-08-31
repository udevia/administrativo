<?php
$pageTitle = 'Presupuestos - mi ERP';
$activeMenu = 'ventas_presupuestos';
ob_start();
?>
<div class="space-y-4" x-data="presupuestosApp()" x-cloak>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 font-bold text-xs">Ventas</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Presupuestos / Cotizaciones</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Historial de presupuestos. Conviértelos en Factura, Pedido o Nota de Entrega.</p>
        </div>
        <a href="/pos" class="bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> Nuevo Presupuesto (POS)
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap gap-3">
        <div class="flex-1 min-w-32">
            <label class="block text-xs font-bold text-slate-600 mb-1">Desde</label>
            <input type="date" x-model="filtros.desde" class="w-full text-xs border border-slate-200 rounded-xl p-2.5">
        </div>
        <div class="flex-1 min-w-32">
            <label class="block text-xs font-bold text-slate-600 mb-1">Hasta</label>
            <input type="date" x-model="filtros.hasta" class="w-full text-xs border border-slate-200 rounded-xl p-2.5">
        </div>
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-bold text-slate-600 mb-1">Buscar</label>
            <input type="text" x-model="filtros.buscar" placeholder="Número o cliente..." class="w-full text-xs border border-slate-200 rounded-xl p-2.5">
        </div>
        <div class="flex items-end">
            <button @click="cargar()" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">
                <i class="fa-solid fa-search mr-1"></i> Buscar
            </button>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-file-lines text-slate-600"></i>
                Presupuestos Emitidos
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 text-slate-600 uppercase font-bold border-b text-[11px]">
                    <tr>
                        <th class="p-3">N° Presupuesto</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Cliente</th>
                        <th class="p-3 text-right">Total USD</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="doc in registrosFiltrados()" :key="doc.id">
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-bold text-slate-700" x-text="doc.numero_documento"></td>
                            <td class="p-3 text-slate-600" x-text="doc.fecha_emision"></td>
                            <td class="p-3 font-sans font-medium text-slate-900" x-text="doc.cliente_nombre"></td>
                            <td class="p-3 text-right font-bold" x-text="'$' + fmt(doc.total_general)"></td>
                            <td class="p-3 text-center">
                                <span :class="doc.estado==='ANULADA'?'bg-red-100 text-red-600':'bg-slate-100 text-slate-600'"
                                      class="text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="doc.estado"></span>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a :href="'/api/ventas/' + doc.id + '/documento'" target="_blank"
                                       class="bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-slate-200 transition" title="Imprimir">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <template x-if="doc.estado !== 'ANULADA'">
                                        <div class="flex gap-1">
                                            <button @click="convertir(doc, 'FACTURA')"
                                                    class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-blue-200 transition" title="Convertir a Factura">
                                                <i class="fa-solid fa-file-invoice-dollar"></i> Factura
                                            </button>
                                            <button @click="convertir(doc, 'PEDIDO')"
                                                    class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-indigo-200 transition" title="Convertir a Pedido">
                                                <i class="fa-solid fa-cart-flatbed"></i> Pedido
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="registros.length === 0">
                        <td colspan="6" class="text-center py-16 text-slate-400 font-sans">
                            <i class="fa-solid fa-file-lines text-4xl block mb-2 text-slate-300"></i>
                            No hay presupuestos en el período seleccionado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function presupuestosApp() {
    return {
        registros: [],
        filtros: {
            desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            hasta: new Date().toISOString().split('T')[0],
            buscar: ''
        },

        async init() { await this.cargar(); },

        async cargar() {
            try {
                const r = await fetch(`/api/ventas/historial?tipo=PRESUPUESTO&desde=${this.filtros.desde}&hasta=${this.filtros.hasta}`);
                const j = await r.json();
                this.registros = j.data || [];
            } catch(e) { this.registros = []; }
        },

        registrosFiltrados() {
            if (!this.filtros.buscar) return this.registros;
            const q = this.filtros.buscar.toLowerCase();
            return this.registros.filter(r =>
                (r.numero_documento||'').toLowerCase().includes(q) ||
                (r.cliente_nombre||'').toLowerCase().includes(q)
            );
        },

        async convertir(doc, tipo) {
            if (!confirm(`¿Convertir presupuesto ${doc.numero_documento} en ${tipo}?`)) return;
            try {
                const r = await fetch(`/api/ventas/${doc.id}/convertir`, {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ nuevo_tipo: tipo })
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Convertido exitosamente.');
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
