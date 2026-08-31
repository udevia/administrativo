<?php
$pageTitle = 'Pedidos y Apartados - mi ERP';
$activeMenu = 'ventas_pedidos';
ob_start();
?>
<div class="space-y-4" x-data="pedidosApp()" x-cloak>
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs">Ventas</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Pedidos / Apartados</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Gestión de pedidos y apartados de clientes. Conviértelos en Facturas o Notas de Entrega cuando estén listos.</p>
        </div>
        <a href="/pos" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> Nuevo Pedido (desde POS)
        </a>
    </div>

    <!-- Filtros + KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-indigo-100 p-4">
            <p class="text-xs text-slate-500 font-medium">Total Pedidos</p>
            <p class="text-2xl font-black font-mono text-slate-800 mt-1" x-text="registros.length"></p>
        </div>
        <div class="bg-white rounded-2xl border border-indigo-100 p-4">
            <p class="text-xs text-slate-500 font-medium">Monto Total</p>
            <p class="text-2xl font-black font-mono text-indigo-700 mt-1" x-text="'$' + totalMonto()"></p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 p-4">
            <p class="text-xs text-slate-500 font-medium">Pendientes</p>
            <p class="text-2xl font-black font-mono text-amber-600 mt-1" x-text="registros.filter(r=>r.estado==='EMITIDA'||r.estado==='PENDIENTE').length"></p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col justify-center">
            <div class="flex gap-2">
                <input type="date" x-model="filtros.desde" class="flex-1 text-xs border border-slate-200 rounded-xl p-2">
                <input type="date" x-model="filtros.hasta" class="flex-1 text-xs border border-slate-200 rounded-xl p-2">
                <button @click="cargar()" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3 py-2 rounded-xl transition">
                    <i class="fa-solid fa-search"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de Pedidos -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b flex justify-between items-center">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-cart-flatbed text-indigo-600"></i>
                Pedidos y Apartados
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 text-slate-600 uppercase font-bold border-b text-[11px]">
                    <tr>
                        <th class="p-3">N° Pedido</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Cliente</th>
                        <th class="p-3 text-right">Total USD</th>
                        <th class="p-3 text-right">Saldo</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="doc in registros" :key="doc.id">
                        <tr class="hover:bg-indigo-50/40 transition">
                            <td class="p-3 font-bold text-indigo-700" x-text="doc.numero_documento"></td>
                            <td class="p-3 text-slate-600" x-text="doc.fecha_emision"></td>
                            <td class="p-3 font-sans font-medium text-slate-900" x-text="doc.cliente_nombre"></td>
                            <td class="p-3 text-right font-bold" x-text="'$' + fmt(doc.total_general)"></td>
                            <td class="p-3 text-right text-amber-700 font-bold" x-text="doc.saldo_pendiente > 0 ? '$'+fmt(doc.saldo_pendiente) : '—'"></td>
                            <td class="p-3 text-center">
                                <span :class="doc.estado==='ANULADA'?'bg-red-100 text-red-600':'bg-indigo-100 text-indigo-700'"
                                      class="text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="doc.estado"></span>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a :href="'/api/ventas/' + doc.id + '/documento'" target="_blank"
                                       class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-indigo-200 transition" title="Imprimir">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <button @click="convertirAFactura(doc)" x-show="doc.estado !== 'ANULADA'"
                                            class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-emerald-200 transition" title="Convertir a Factura">
                                        <i class="fa-solid fa-file-invoice-dollar"></i> Facturar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="registros.length === 0">
                        <td colspan="7" class="text-center py-16 text-slate-400 font-sans">
                            <i class="fa-solid fa-cart-flatbed text-4xl block mb-2 text-slate-300"></i>
                            No hay pedidos en el período seleccionado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function pedidosApp() {
    return {
        registros: [],
        filtros: {
            desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            hasta: new Date().toISOString().split('T')[0],
        },

        async init() { await this.cargar(); },

        async cargar() {
            try {
                const r = await fetch(`/api/ventas/historial?tipo=PEDIDO&desde=${this.filtros.desde}&hasta=${this.filtros.hasta}`);
                const j = await r.json();
                this.registros = j.data || [];
            } catch(e) { this.registros = []; }
        },

        totalMonto() {
            return this.fmt(this.registros.reduce((s,r)=>s+Number(r.total_general||0),0));
        },

        async convertirAFactura(doc) {
            if (!confirm(`¿Convertir el pedido ${doc.numero_documento} en Factura?`)) return;
            try {
                const r = await fetch(`/api/ventas/${doc.id}/convertir`, {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ nuevo_tipo: 'FACTURA' })
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Pedido convertido a Factura exitosamente.');
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
