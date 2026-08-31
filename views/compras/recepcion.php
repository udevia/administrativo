<?php
$pageTitle = 'Recepción de Mercancía - mi ERP';
$activeMenu = 'compras_recepcion';
ob_start();
?>
<div class="space-y-4" x-data="recepcionApp()" x-cloak>
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">Compras</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Recepción de Mercancía</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Confirme la recepción de OCs pendientes. Se actualizará el inventario y se generará la factura de compra correspondiente.</p>
        </div>
        <button @click="cargarOCPendientes()" class="bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-rotate"></i> Refrescar OCs Pendientes
        </button>
    </div>

    <!-- Lista de OCs Pendientes -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-amber-50 border-b border-amber-100 flex items-center gap-2">
            <i class="fa-solid fa-clock text-amber-600"></i>
            <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Órdenes de Compra Pendientes de Recepción</span>
            <span class="ml-auto text-xs font-mono font-bold text-amber-600 bg-amber-100 px-2 py-0.5 rounded-full" x-text="ocPendientes.length + ' OC(s)'"></span>
        </div>
        <div class="p-4 space-y-3">
            <div x-show="ocPendientes.length === 0" class="text-center py-12 text-slate-400">
                <i class="fa-solid fa-truck-ramp-box text-4xl block mb-2 text-slate-300"></i>
                <p class="font-sans">No hay órdenes de compra pendientes de recepción.</p>
                <a href="/compras/orden" class="text-teal-600 hover:text-teal-800 font-bold text-xs mt-2 inline-block">
                    <i class="fa-solid fa-file-circle-plus mr-1"></i>Emitir una Orden de Compra
                </a>
            </div>

            <template x-for="oc in ocPendientes" :key="oc.id">
                <div class="border border-amber-200 rounded-2xl overflow-hidden bg-amber-50/30">
                    <!-- Cabecera OC -->
                    <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white border-b border-amber-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-black text-amber-700" x-text="oc.numero_factura"></span>
                                <span class="text-[10px] font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">PENDIENTE</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-0.5" x-text="oc.proveedor_nombre + ' — Emitida: ' + oc.fecha_emision"></p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">N° Factura Proveedor</label>
                                <input type="text" x-model="oc._numFacturaProv" :placeholder="'FAC-PROV-' + oc.id"
                                       class="text-xs border border-slate-200 rounded-xl p-2 font-mono w-40 focus:outline-none focus:border-teal-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Depósito Recepción</label>
                                <select x-model.number="oc._depositoId" class="text-xs border border-slate-200 rounded-xl p-2 w-44">
                                    <template x-for="d in depositos" :key="d.id">
                                        <option :value="d.id" x-text="d.descripcion"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button @click="procesarRecepcion(oc)" :disabled="oc._procesando"
                                        class="bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white font-bold text-xs px-4 py-2 rounded-xl transition flex items-center gap-2">
                                    <i class="fa-solid fa-spinner fa-spin" x-show="oc._procesando"></i>
                                    <i class="fa-solid fa-truck-ramp-box" x-show="!oc._procesando"></i>
                                    <span x-text="oc._procesando ? 'Procesando...' : 'Confirmar Recepción'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Items de la OC -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-[11px]">
                            <thead class="bg-slate-100 text-slate-600 font-bold text-[10px]">
                                <tr>
                                    <th class="p-2.5 text-left">Código</th>
                                    <th class="p-2.5 text-left">Producto</th>
                                    <th class="p-2.5 text-center">Cant. Ordenada</th>
                                    <th class="p-2.5 text-center">Cant. a Recibir</th>
                                    <th class="p-2.5 text-right">Costo Unit.</th>
                                    <th class="p-2.5 text-right">Subtotal</th>
                                    <th class="p-2.5 text-center" x-show="oc.items?.some(i=>i.maneja_seriales)">Seriales</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono">
                                <template x-for="(item, idx) in (oc.items || [])" :key="idx">
                                    <tr class="hover:bg-white transition">
                                        <td class="p-2.5 font-bold text-teal-700" x-text="item.codigo"></td>
                                        <td class="p-2.5 font-sans font-medium text-slate-900" x-text="item.descripcion"></td>
                                        <td class="p-2.5 text-center text-slate-600" x-text="Number(item.cantidad).toFixed(2)"></td>
                                        <td class="p-2.5 text-center">
                                            <input type="number" min="0" :max="item.cantidad" step="0.01"
                                                   x-model.number="item._cantRecibir"
                                                   :value="item._cantRecibir ?? item.cantidad"
                                                   @change="item._cantRecibir = Number($event.target.value)"
                                                   class="w-20 border border-teal-200 bg-teal-50 rounded-lg p-1.5 text-center font-mono font-bold text-teal-800 focus:outline-none focus:border-teal-500">
                                        </td>
                                        <td class="p-2.5 text-right" x-text="'$' + fmt(item.costo_unitario)"></td>
                                        <td class="p-2.5 text-right font-bold text-slate-800" x-text="'$' + fmt((item._cantRecibir ?? item.cantidad) * item.costo_unitario)"></td>
                                        <td class="p-2.5 text-center" x-show="oc.items?.some(i=>i.maneja_seriales)">
                                            <template x-if="item.maneja_seriales">
                                                <button @click="abrirSeriales(oc, item, idx)"
                                                        :class="item._seriales?.length === (item._cantRecibir ?? item.cantidad) ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-purple-50 text-purple-700 border-purple-200'"
                                                        class="font-bold text-[10px] px-2 py-1 rounded-lg border transition">
                                                    <i class="fa-solid fa-microchip mr-1"></i>
                                                    <span x-text="(item._seriales?.length||0) + '/' + (item._cantRecibir ?? item.cantidad)"></span>
                                                </button>
                                            </template>
                                            <span x-show="!item.maneja_seriales" class="text-slate-300">—</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t border-slate-200">
                                <tr>
                                    <td colspan="5" class="p-2.5 text-right font-bold text-slate-700 text-xs">TOTAL OC:</td>
                                    <td class="p-2.5 text-right font-black font-mono text-teal-700"
                                        x-text="'$' + fmt((oc.items||[]).reduce((s,i)=>s+((i._cantRecibir??i.cantidad)*i.costo_unitario),0))"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Historial de Recepciones -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-check-double text-emerald-600"></i> Historial de Recepciones
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 text-slate-600 uppercase font-bold border-b text-[11px]">
                    <tr>
                        <th class="p-3">N° Recepción</th>
                        <th class="p-3">OC Origen</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Proveedor</th>
                        <th class="p-3 text-right">Total</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="rec in historial" :key="rec.id">
                        <tr class="hover:bg-emerald-50/30 transition">
                            <td class="p-3 font-bold text-teal-700" x-text="rec.numero_factura"></td>
                            <td class="p-3 text-slate-500" x-text="rec.oc_numero || '—'"></td>
                            <td class="p-3 text-slate-600" x-text="rec.fecha_emision"></td>
                            <td class="p-3 font-sans font-medium text-slate-900" x-text="rec.proveedor_nombre"></td>
                            <td class="p-3 text-right font-bold" x-text="'$' + fmt(rec.monto_total)"></td>
                            <td class="p-3 text-center">
                                <a :href="'/api/compras/' + rec.id + '/documento'" target="_blank"
                                   class="bg-teal-50 hover:bg-teal-100 text-teal-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-teal-200 transition">
                                    <i class="fa-solid fa-print"></i> Imprimir
                                </a>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="historial.length === 0">
                        <td colspan="6" class="text-center py-10 text-slate-400 font-sans">No hay recepciones registradas este mes.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Ingreso de Seriales -->
    <div x-show="modalSeriales" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-microchip text-purple-600"></i>
                Ingreso de Seriales / IMEI
            </h3>
            <p class="text-xs text-slate-500" x-text="'Producto: ' + (itemActual?.descripcion || '')"></p>
            <p class="text-xs font-bold text-purple-700" x-text="'Se requieren: ' + (itemActual?._cantRecibir ?? 0) + ' serial(es)'"></p>

            <!-- Ingreso masivo -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-2">
                    Pegar lista de seriales (uno por línea o separados por coma):
                </label>
                <textarea x-model="serialesTexto" rows="5" @input="parseSeriales()"
                          placeholder="S/N-001&#10;S/N-002&#10;S/N-003&#10;..."
                          class="w-full border border-purple-200 bg-purple-50 rounded-xl p-3 font-mono text-xs resize-none focus:outline-none focus:border-purple-500"></textarea>
                <p class="text-[10px] text-slate-500 mt-1" x-text="serialesArray.length + ' serial(es) detectado(s)'"></p>
            </div>

            <!-- Lista de seriales -->
            <div x-show="serialesArray.length > 0" class="max-h-40 overflow-y-auto border border-purple-100 rounded-xl p-2 space-y-1">
                <template x-for="(s, i) in serialesArray" :key="i">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-[10px]" x-text="i+1"></span>
                        <span class="font-mono" x-text="s"></span>
                    </div>
                </template>
            </div>

            <div class="flex gap-2 justify-end">
                <button @click="modalSeriales = false" class="px-4 py-2 text-slate-500 font-bold text-sm">Cancelar</button>
                <button @click="confirmarSeriales()"
                        :disabled="serialesArray.length === 0"
                        class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-bold px-6 py-2 rounded-xl transition text-sm">
                    Confirmar Seriales
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function recepcionApp() {
    return {
        ocPendientes: [],
        historial: [],
        depositos: [],
        modalSeriales: false,
        itemActual: null,
        ocActual: null,
        idxActual: -1,
        serialesTexto: '',
        serialesArray: [],

        async init() {
            await Promise.all([this.cargarOCPendientes(), this.cargarHistorial(), this.cargarDepositos()]);
        },

        async cargarDepositos() {
            try {
                const r = await fetch('/api/inventario/depositos');
                const j = await r.json();
                this.depositos = j.data || [{ id: 1, descripcion: 'Depósito Principal' }];
            } catch(e) { this.depositos = [{ id: 1, descripcion: 'Depósito Principal' }]; }
        },

        async cargarOCPendientes() {
            try {
                const r = await fetch('/api/compras/ordenes/pendientes');
                const j = await r.json();
                this.ocPendientes = (j.data || []).map(oc => ({
                    ...oc,
                    _numFacturaProv: '',
                    _depositoId: oc.deposito_id || 1,
                    _procesando: false,
                    items: (oc.items || []).map(i => ({
                        ...i,
                        _cantRecibir: Number(i.cantidad),
                        _seriales: []
                    }))
                }));
            } catch(e) { this.ocPendientes = []; }
        },

        async cargarHistorial() {
            try {
                const r = await fetch('/api/compras/historial?tipo=FACTURA&desde=' + new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0] + '&hasta=' + new Date().toISOString().split('T')[0]);
                const j = await r.json();
                this.historial = j.data || [];
            } catch(e) { this.historial = []; }
        },

        async procesarRecepcion(oc) {
            if (!confirm(`¿Confirmar recepción de la OC ${oc.numero_factura}? Esto actualizará el inventario.`)) return;
            oc._procesando = true;
            try {
                const items = (oc.items || []).filter(i => (i._cantRecibir ?? i.cantidad) > 0).map(i => ({
                    producto_id: i.producto_id,
                    cantidad: i._cantRecibir ?? i.cantidad,
                    costo_unitario: i.costo_unitario,
                    porcentaje_descuento: 0,
                    porcentaje_iva: 16,
                    seriales: i._seriales || []
                }));

                const r = await fetch('/api/compras/recepcion', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        orden_id: oc.id,
                        numero_factura_proveedor: oc._numFacturaProv || ('RCP-' + Date.now()),
                        deposito_id: oc._depositoId,
                        items
                    })
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Recepción procesada. Inventario actualizado.');
                    if (j.data?.compra_id) window.open('/api/compras/' + j.data.compra_id + '/documento', '_blank');
                    await Promise.all([this.cargarOCPendientes(), this.cargarHistorial()]);
                } else { alert('❌ ' + j.message); }
            } catch(e) { alert('Error: ' + e.message); }
            finally { oc._procesando = false; }
        },

        abrirSeriales(oc, item, idx) {
            this.ocActual = oc;
            this.itemActual = item;
            this.idxActual = idx;
            this.serialesTexto = (item._seriales || []).join('\n');
            this.parseSeriales();
            this.modalSeriales = true;
        },

        parseSeriales() {
            const texto = this.serialesTexto.trim();
            if (!texto) { this.serialesArray = []; return; }
            this.serialesArray = texto.split(/[\n,;]+/).map(s=>s.trim()).filter(s=>s.length>0);
        },

        confirmarSeriales() {
            if (this.itemActual) {
                this.itemActual._seriales = [...this.serialesArray];
            }
            this.modalSeriales = false;
        },

        fmt(val) { return Number(val||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
