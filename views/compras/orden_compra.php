<?php
$pageTitle = 'Orden de Compra - mi ERP';
$activeMenu = 'compras_orden';
ob_start();
?>
<div class="space-y-4" x-data="ordenCompraApp()" x-cloak>
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs">Compras</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Órdenes de Compra</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Emita órdenes de compra a proveedores. Las OC pendientes se convierten en recepciones de mercancía.</p>
        </div>
        <button @click="abrirNueva()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-file-circle-plus"></i> Nueva Orden de Compra
        </button>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500">OC Emitidas</p>
            <p class="text-2xl font-black font-mono text-slate-800 mt-1" x-text="ordenes.length"></p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 p-4">
            <p class="text-xs text-slate-500">Pendientes Recepción</p>
            <p class="text-2xl font-black font-mono text-amber-600 mt-1" x-text="ordenes.filter(o=>o.estado==='PENDIENTE').length"></p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 p-4">
            <p class="text-xs text-slate-500">Recibidas</p>
            <p class="text-2xl font-black font-mono text-emerald-700 mt-1" x-text="ordenes.filter(o=>o.estado==='RECIBIDA').length"></p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Monto Total</p>
            <p class="text-2xl font-black font-mono text-slate-700 mt-1" x-text="'$' + fmt(ordenes.reduce((s,o)=>s+Number(o.monto_total||0),0))"></p>
        </div>
    </div>

    <!-- Tabla de OCs -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-file-circle-plus text-emerald-600"></i> Órdenes de Compra
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 text-slate-600 uppercase font-bold border-b text-[11px]">
                    <tr>
                        <th class="p-3">N° OC</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Proveedor</th>
                        <th class="p-3 text-right">Monto USD</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="oc in ordenes" :key="oc.id">
                        <tr class="hover:bg-emerald-50/40 transition">
                            <td class="p-3 font-bold text-emerald-700" x-text="oc.numero_factura"></td>
                            <td class="p-3 text-slate-600" x-text="oc.fecha_emision"></td>
                            <td class="p-3 font-sans font-medium text-slate-900" x-text="oc.proveedor_nombre"></td>
                            <td class="p-3 text-right font-bold" x-text="'$' + fmt(oc.monto_total)"></td>
                            <td class="p-3 text-center">
                                <span :class="{
                                    'bg-amber-100 text-amber-700': oc.estado==='PENDIENTE',
                                    'bg-emerald-100 text-emerald-700': oc.estado==='RECIBIDA',
                                    'bg-red-100 text-red-600': oc.estado==='ANULADA'
                                }" class="text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="oc.estado"></span>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a :href="'/api/compras/' + oc.id + '/documento'" target="_blank"
                                       class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-emerald-200 transition" title="Imprimir OC">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <a x-show="oc.estado === 'PENDIENTE'" href="/compras/recepcion"
                                       class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-blue-200 transition" title="Ir a Recepción">
                                        <i class="fa-solid fa-truck-ramp-box"></i> Recibir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="ordenes.length === 0">
                        <td colspan="6" class="text-center py-16 text-slate-400 font-sans">
                            <i class="fa-solid fa-file-circle-plus text-4xl block mb-2 text-slate-300"></i>
                            No hay órdenes de compra registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nueva OC -->
    <div x-show="modalNueva" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[90vh]">
            <div class="bg-gradient-to-r from-emerald-700 to-green-900 text-white p-5 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold">Nueva Orden de Compra</h2>
                        <p class="text-[10px] text-emerald-200">Emita una OC a su proveedor seleccionado</p>
                    </div>
                </div>
                <button @click="modalNueva = false" class="text-white/60 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6 space-y-4 text-xs overflow-y-auto flex-1">
                <!-- Cabecera OC -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1.5">Proveedor *</label>
                        <select x-model.number="form.proveedor_id" class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-emerald-500">
                            <option value="">— Seleccione proveedor —</option>
                            <template x-for="p in proveedores" :key="p.id">
                                <option :value="p.id" x-text="p.razon_social + ' (' + p.documento_fiscal + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1.5">Depósito Destino *</label>
                        <select x-model.number="form.deposito_id" class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-emerald-500">
                            <template x-for="d in depositos" :key="d.id">
                                <option :value="d.id" x-text="d.descripcion"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1.5">Condición de Pago</label>
                        <select x-model="form.condicion_pago" class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-emerald-500">
                            <option value="CONTADO">Contado</option>
                            <option value="CREDITO">Crédito</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-600 mb-1.5">Observaciones / Condiciones</label>
                    <textarea x-model="form.nota" rows="2" placeholder="Ej: Entrega en 5 días, precio CIF..." class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-emerald-500 resize-none"></textarea>
                </div>

                <!-- Buscador de Productos -->
                <div class="border-t border-slate-100 pt-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold text-slate-700 flex items-center gap-2">
                            <i class="fa-solid fa-boxes-stacked text-emerald-600"></i> Renglones de la Orden
                        </h4>
                        <button @click="agregarRenglon()" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs px-3 py-1.5 rounded-lg border border-emerald-200 transition">
                            <i class="fa-solid fa-plus mr-1"></i>Agregar Producto
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-[11px]">
                            <thead class="bg-slate-100 font-bold text-slate-700">
                                <tr>
                                    <th class="p-2 text-left">Producto *</th>
                                    <th class="p-2 text-center w-24">Cantidad</th>
                                    <th class="p-2 text-right w-28">Costo Unit. ($)</th>
                                    <th class="p-2 text-right w-24">Subtotal</th>
                                    <th class="p-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, idx) in items" :key="idx">
                                    <tr>
                                        <td class="p-2">
                                            <select x-model.number="item.producto_id" class="w-full border border-slate-200 rounded-lg p-1.5 focus:outline-none focus:border-emerald-500">
                                                <option value="">— Seleccione producto —</option>
                                                <template x-for="p in productos" :key="p.id">
                                                    <option :value="p.id" x-text="p.codigo + ' - ' + p.descripcion"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="p-2 text-center">
                                            <input type="number" min="1" step="0.01" x-model.number="item.cantidad"
                                                   class="w-20 border border-slate-200 rounded-lg p-1.5 text-center font-mono focus:outline-none focus:border-emerald-500">
                                        </td>
                                        <td class="p-2 text-right">
                                            <input type="number" min="0" step="0.01" x-model.number="item.costo_unitario"
                                                   class="w-24 border border-slate-200 rounded-lg p-1.5 text-right font-mono font-bold text-emerald-700 focus:outline-none focus:border-emerald-500">
                                        </td>
                                        <td class="p-2 text-right font-mono font-bold text-slate-800" x-text="'$' + fmt(item.cantidad * item.costo_unitario)"></td>
                                        <td class="p-2 text-center">
                                            <button @click="items.splice(idx, 1)" class="text-red-400 hover:text-red-600">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="5" class="text-center py-8 text-slate-400">
                                        Agregue al menos un producto a la orden
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t border-slate-200">
                                <tr>
                                    <td colspan="3" class="p-2 text-right font-bold text-slate-700">TOTAL OC:</td>
                                    <td class="p-2 text-right font-black font-mono text-emerald-700 text-sm" x-text="'$' + fmt(totalOC())"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                    <button @click="modalNueva = false" class="px-4 py-2 text-slate-500 font-bold hover:text-slate-800">Cancelar</button>
                    <button @click="guardar()" :disabled="guardando || !form.proveedor_id || items.length === 0 || items.some(i=>!i.producto_id)"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold px-6 py-2.5 rounded-xl transition flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin" x-show="guardando"></i>
                        <i class="fa-solid fa-file-circle-plus" x-show="!guardando"></i>
                        <span x-text="guardando ? 'Emitiendo...' : 'Emitir Orden de Compra'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ordenCompraApp() {
    return {
        ordenes: [],
        proveedores: [],
        depositos: [],
        productos: [],
        modalNueva: false,
        guardando: false,
        items: [],
        form: { proveedor_id: '', deposito_id: 1, condicion_pago: 'CONTADO', nota: '' },

        async init() {
            await Promise.all([
                this.cargarOrdenes(),
                this.cargarProveedores(),
                this.cargarDepositos(),
                this.cargarProductos()
            ]);
        },

        async cargarOrdenes() {
            try {
                const r = await fetch('/api/compras/ordenes');
                const j = await r.json();
                this.ordenes = j.data || [];
            } catch(e) {}
        },
        async cargarProveedores() {
            try {
                const r = await fetch('/api/proveedores');
                const j = await r.json();
                this.proveedores = j.data || [];
            } catch(e) {}
        },
        async cargarDepositos() {
            try {
                const r = await fetch('/api/inventario/depositos');
                const j = await r.json();
                this.depositos = j.data || [{ id: 1, descripcion: 'Depósito Principal' }];
            } catch(e) { this.depositos = [{ id: 1, descripcion: 'Depósito Principal' }]; }
        },
        async cargarProductos() {
            try {
                const r = await fetch('/api/maestros/productos?limit=2000');
                const j = await r.json();
                this.productos = j.data || [];
            } catch(e) {}
        },

        abrirNueva() {
            this.form = { proveedor_id: '', deposito_id: this.depositos[0]?.id || 1, condicion_pago: 'CONTADO', nota: '' };
            this.items = [];
            this.modalNueva = true;
        },

        agregarRenglon() {
            this.items.push({ producto_id: '', cantidad: 1, costo_unitario: 0, porcentaje_descuento: 0 });
        },

        totalOC() {
            return this.items.reduce((s, i) => s + (Number(i.cantidad || 0) * Number(i.costo_unitario || 0)), 0);
        },

        async guardar() {
            if (!this.form.proveedor_id || this.items.length === 0) return;
            this.guardando = true;
            try {
                const payload = {
                    cabecera: {
                        proveedor_id: this.form.proveedor_id,
                        deposito_id: this.form.deposito_id,
                        condicion_pago: this.form.condicion_pago,
                        nota: this.form.nota,
                        usuario_id: 1
                    },
                    items: this.items.filter(i => i.producto_id).map(i => ({
                        producto_id: i.producto_id,
                        cantidad: i.cantidad,
                        costo_unitario: i.costo_unitario,
                        porcentaje_descuento: 0
                    }))
                };
                const r = await fetch('/api/compras/orden', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(payload)
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Orden de Compra emitida exitosamente.');
                    this.modalNueva = false;
                    await this.cargarOrdenes();
                    // Abrir impresión de la OC
                    if (j.data?.compra_id) window.open('/api/compras/' + j.data.compra_id + '/documento', '_blank');
                } else { alert('❌ ' + j.message); }
            } catch(e) { alert('Error: ' + e.message); }
            finally { this.guardando = false; }
        },

        fmt(val) { return Number(val||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
