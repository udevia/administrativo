<?php
$pageTitle = 'Facturación - mi ERP';
$activeMenu = 'ventas_facturacion';
ob_start();
?>
<div class="space-y-4" x-data="facturacionApp()" x-cloak>
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs">Ventas</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Gestión de Facturas</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Historial de facturas emitidas con impresión, anulación y conversión de documentos.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="/pos" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i> Nueva Factura (POS)
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Desde</label>
                <input type="date" x-model="filtros.desde" class="w-full text-xs border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Hasta</label>
                <input type="date" x-model="filtros.hasta" class="w-full text-xs border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Estado</label>
                <select x-model="filtros.estado" class="w-full text-xs border border-slate-200 rounded-xl p-2.5">
                    <option value="">Todos</option>
                    <option value="PAGADA">Pagadas</option>
                    <option value="PENDIENTE">Pendientes (Crédito)</option>
                    <option value="ANULADA">Anuladas</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Buscar</label>
                <input type="text" x-model="filtros.buscar" placeholder="N° Doc o cliente..." class="w-full text-xs border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex items-end">
                <button @click="cargar()" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">
                    <i class="fa-solid fa-search mr-1"></i> Buscar
                </button>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 font-medium">Total Facturas</p>
            <p class="text-2xl font-black font-mono text-slate-800 mt-1" x-text="registros.length"></p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 font-medium">Total Facturado</p>
            <p class="text-2xl font-black font-mono text-emerald-700 mt-1" x-text="'$' + totalFacturado()"></p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 font-medium">Saldo Pendiente</p>
            <p class="text-2xl font-black font-mono text-amber-600 mt-1" x-text="'$' + totalPendiente()"></p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 font-medium">Anuladas</p>
            <p class="text-2xl font-black font-mono text-red-500 mt-1" x-text="registros.filter(r=>r.estado==='ANULADA').length"></p>
        </div>
    </div>

    <!-- Tabla de Facturas -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b flex justify-between items-center">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-file-invoice-dollar text-blue-600"></i>
                Facturas de Venta
            </span>
            <span class="text-xs text-slate-400 font-mono" x-text="registros.length + ' registros'"></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 text-slate-600 uppercase font-bold border-b text-[11px]">
                    <tr>
                        <th class="p-3">N° Documento</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Cliente</th>
                        <th class="p-3">RIF/CI</th>
                        <th class="p-3 text-center">Condición</th>
                        <th class="p-3 text-right">Subtotal</th>
                        <th class="p-3 text-right">IVA</th>
                        <th class="p-3 text-right font-bold text-slate-800">Total USD</th>
                        <th class="p-3 text-right text-amber-600">Saldo</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="doc in registrosFiltrados()" :key="doc.id">
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="p-3 font-bold text-blue-700" x-text="doc.numero_documento || doc.numero_factura"></td>
                            <td class="p-3 text-slate-600" x-text="doc.fecha_emision"></td>
                            <td class="p-3 font-sans font-medium text-slate-900 max-w-[180px] truncate" x-text="doc.cliente_nombre"></td>
                            <td class="p-3 text-slate-500" x-text="doc.documento_fiscal"></td>
                            <td class="p-3 text-center">
                                <span :class="doc.condicion_pago === 'CREDITO' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                                      class="text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="doc.condicion_pago"></span>
                            </td>
                            <td class="p-3 text-right" x-text="'$' + fmt(doc.subtotal_neto)"></td>
                            <td class="p-3 text-right text-red-500" x-text="'$' + fmt(doc.monto_iva)"></td>
                            <td class="p-3 text-right font-bold text-slate-900" x-text="'$' + fmt(doc.total_general)"></td>
                            <td class="p-3 text-right font-bold text-amber-700" x-text="doc.saldo_pendiente > 0 ? '$' + fmt(doc.saldo_pendiente) : '—'"></td>
                            <td class="p-3 text-center">
                                <span :class="{
                                    'bg-emerald-100 text-emerald-700': doc.estado === 'PAGADA',
                                    'bg-amber-100 text-amber-700': doc.estado === 'PENDIENTE',
                                    'bg-red-100 text-red-600': doc.estado === 'ANULADA',
                                    'bg-slate-100 text-slate-600': !['PAGADA','PENDIENTE','ANULADA'].includes(doc.estado)
                                }" class="text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="doc.estado"></span>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a :href="'/api/ventas/' + doc.id + '/documento'" target="_blank"
                                       class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-blue-200 transition flex items-center gap-1" title="Imprimir/Ver">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <button @click="convertir(doc)"
                                            x-show="doc.estado !== 'ANULADA'"
                                            class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-indigo-200 transition flex items-center gap-1" title="Convertir Documento">
                                        <i class="fa-solid fa-arrows-rotate"></i>
                                    </button>
                                    <button @click="anular(doc)"
                                            x-show="doc.estado !== 'ANULADA'"
                                            class="bg-red-50 hover:bg-red-100 text-red-600 font-bold text-[10px] px-2 py-1 rounded-lg border border-red-200 transition flex items-center gap-1" title="Anular">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="registros.length === 0">
                        <td colspan="11" class="text-center py-16 text-slate-400 font-sans">
                            <i class="fa-solid fa-file-invoice-dollar text-4xl block mb-2 text-slate-300"></i>
                            No se encontraron facturas para los filtros seleccionados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Convertir -->
    <div x-show="modalConvertir" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="font-bold text-slate-800">Convertir Documento</h3>
            <p class="text-xs text-slate-500">Documento origen: <strong x-text="docSeleccionado?.numero_documento"></strong></p>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-2">Tipo de documento destino:</label>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="tipo in tiposConversion" :key="tipo.value">
                        <label :class="tipoDestino === tipo.value ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                               class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-bold">
                            <input type="radio" :value="tipo.value" x-model="tipoDestino" class="hidden">
                            <i :class="tipo.icon"></i>
                            <span x-text="tipo.label"></span>
                        </label>
                    </template>
                </div>
            </div>
            <div class="flex gap-2 justify-end pt-2">
                <button @click="modalConvertir = false" class="px-4 py-2 text-slate-500 font-bold text-sm hover:text-slate-800">Cancelar</button>
                <button @click="ejecutarConversion()" :disabled="!tipoDestino"
                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold px-6 py-2 rounded-xl transition text-sm">
                    Convertir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function facturacionApp() {
    return {
        registros: [],
        cargando: false,
        modalConvertir: false,
        docSeleccionado: null,
        tipoDestino: '',
        filtros: {
            desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            hasta: new Date().toISOString().split('T')[0],
            estado: '',
            buscar: ''
        },
        tiposConversion: [
            { value: 'NOTA_ENTREGA', label: 'Nota Entrega', icon: 'fa-solid fa-file-export' },
            { value: 'PEDIDO',       label: 'Pedido',       icon: 'fa-solid fa-cart-flatbed' },
            { value: 'PRESUPUESTO',  label: 'Presupuesto',  icon: 'fa-solid fa-file-lines' },
        ],

        async init() { await this.cargar(); },

        async cargar() {
            this.cargando = true;
            try {
                const r = await fetch(`/api/ventas/historial?tipo=FACTURA&desde=${this.filtros.desde}&hasta=${this.filtros.hasta}`);
                const j = await r.json();
                this.registros = j.data || [];
            } catch(e) { this.registros = []; }
            finally { this.cargando = false; }
        },

        registrosFiltrados() {
            let data = this.registros;
            if (this.filtros.estado) data = data.filter(r => r.estado === this.filtros.estado);
            if (this.filtros.buscar) {
                const q = this.filtros.buscar.toLowerCase();
                data = data.filter(r =>
                    (r.numero_documento || '').toLowerCase().includes(q) ||
                    (r.cliente_nombre || '').toLowerCase().includes(q) ||
                    (r.documento_fiscal || '').toLowerCase().includes(q)
                );
            }
            return data;
        },

        totalFacturado() {
            return this.fmt(this.registros.filter(r=>r.estado!=='ANULADA').reduce((s,r)=>s+Number(r.total_general||0),0));
        },
        totalPendiente() {
            return this.fmt(this.registros.reduce((s,r)=>s+Number(r.saldo_pendiente||0),0));
        },

        convertir(doc) {
            this.docSeleccionado = doc;
            this.tipoDestino = '';
            this.modalConvertir = true;
        },

        async ejecutarConversion() {
            if (!this.tipoDestino || !this.docSeleccionado) return;
            try {
                const r = await fetch(`/api/ventas/${this.docSeleccionado.id}/convertir`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nuevo_tipo: this.tipoDestino })
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Documento convertido exitosamente.');
                    this.modalConvertir = false;
                    await this.cargar();
                } else { alert('❌ ' + j.message); }
            } catch(e) { alert('Error: ' + e.message); }
        },

        async anular(doc) {
            if (!confirm(`¿Anular la factura ${doc.numero_documento}? Esta acción no se puede deshacer.`)) return;
            try {
                const r = await fetch(`/api/ventas/${doc.id}/anular`, { method: 'POST' });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Factura anulada correctamente.');
                    await this.cargar();
                } else { alert('❌ ' + j.message); }
            } catch(e) { alert('Error: ' + e.message); }
        },

        fmt(val) {
            return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
