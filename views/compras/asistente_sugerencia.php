<?php
$pageTitle = 'Sugerencia Inteligente de Compras - mi';
$activeMenu = 'compras';
ob_start();
?>
<div class="space-y-4" x-data="asistenteComprasApp()">
    <!-- Encabezado y Filtros de Proyección -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-brain-circuit text-blue-600"></i> Sugerencia Inteligente de Compras y Reabastecimiento
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Calcula la demanda real restando stock comprometido y sumando mercancía en tránsito</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="generarOrdenCompra()" :disabled="seleccionados.length === 0 || procesando" class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    <span>Generar Orden de Compra (<span x-text="seleccionados.length"></span>)</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <div>
                <label class="block font-bold text-slate-600 mb-1">Histórico de Ventas (Días):</label>
                <select x-model.number="filtros.dias_historico" @change="consultar()" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-semibold">
                    <option value="15">Últimos 15 días (Alta rotación)</option>
                    <option value="30">Últimos 30 días (Mes regular)</option>
                    <option value="60">Últimos 60 días (Bimestre)</option>
                    <option value="90">Últimos 90 días (Tendencia trimestral)</option>
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-600 mb-1">Filtrar por Proveedor:</label>
                <select x-model="filtros.proveedor_id" @change="consultar()" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-semibold">
                    <option value="">-- Todos los Proveedores --</option>
                    <template x-for="pr in proveedores" :key="pr.id">
                        <option :value="pr.id" x-text="pr.razon_social"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-600 mb-1">Mostrar Solo:</label>
                <select x-model="filtros.solo_criticos" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-semibold">
                    <option value="true">⚠️ Solo productos que requieren compra</option>
                    <option value="false">📦 Todo el catálogo de inventario</option>
                </select>
            </div>
            <div class="flex items-end">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-2.5 w-full flex justify-between items-center">
                    <span class="text-blue-700 font-semibold text-xs">Inversión Estimada:</span>
                    <span class="font-mono font-black text-blue-900 text-sm" x-text="'$' + totalInversionEstimada.toFixed(2)"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Proyección -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto max-h-[600px]">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 text-slate-700 uppercase font-bold border-b sticky top-0 z-10">
                    <tr>
                        <th class="p-3 text-center w-8"><input type="checkbox" @change="toggleTodos($event)"></th>
                        <th class="p-3">Código / Producto</th>
                        <th class="p-3">Proveedor Habitual</th>
                        <th class="p-3 text-center">Venta Prom/Día</th>
                        <th class="p-3 text-center text-blue-700">Disp.</th>
                        <th class="p-3 text-center text-amber-600">Tránsito</th>
                        <th class="p-3 text-center">Mínimo</th>
                        <th class="p-3 text-center">Máximo</th>
                        <th class="p-3 text-right text-emerald-700 font-bold">Sugerido</th>
                        <th class="p-3 text-right">Inversión $</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="p in productosFiltrados" :key="p.producto_id">
                        <tr :class="p.requiere_compra ? 'bg-amber-50/40 hover:bg-amber-100/40' : 'hover:bg-slate-50'">
                            <td class="p-3 text-center">
                                <input type="checkbox" :value="p" x-model="seleccionados" :disabled="p.cantidad_sugerida <= 0">
                            </td>
                            <td class="p-3 font-sans">
                                <span class="font-mono text-blue-700 font-bold text-[11px]" x-text="p.codigo"></span><br>
                                <span class="font-semibold text-slate-900" x-text="p.descripcion"></span>
                            </td>
                            <td class="p-3 font-sans text-slate-600 text-[11px]" x-text="p.proveedor_nombre || 'N/A'"></td>
                            <td class="p-3 text-center text-slate-700 font-bold" x-text="p.venta_promedio_diaria"></td>
                            <td class="p-3 text-center text-blue-700 font-bold" x-text="p.stock_disponible"></td>
                            <td class="p-3 text-center text-amber-600 font-bold" x-text="p.stock_en_transito"></td>
                            <td class="p-3 text-center text-slate-600" x-text="p.stock_minimo_aplicado"></td>
                            <td class="p-3 text-center text-slate-600" x-text="p.stock_maximo_aplicado"></td>
                            <td class="p-3 text-right font-bold text-emerald-700 font-mono text-sm" x-text="p.cantidad_sugerida"></td>
                            <td class="p-3 text-right font-bold text-slate-800 font-mono" x-text="'$' + (p.inversion_estimada || 0).toFixed(2)"></td>
                        </tr>
                    </template>
                    <tr x-show="productosFiltrados.length === 0">
                        <td colspan="10" class="text-center py-12 text-slate-400 font-sans">
                            No se encontraron productos para los criterios seleccionados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function asistenteComprasApp() {
    return {
        filtros: { dias_historico: 30, proveedor_id: '', solo_criticos: 'true' },
        proveedores: [],
        productos: [],
        seleccionados: [],
        procesando: false,
        init() {
            this.cargarProveedores();
            this.consultar();
        },
        async cargarProveedores() {
            try {
                const res = await fetch('/api/proveedores');
                const json = await res.json();
                this.proveedores = json.data || [];
            } catch(e) {}
        },
        async consultar() {
            try {
                const res = await fetch(`/api/compras/sugerencias?dias_historico=${this.filtros.dias_historico}&proveedor_id=${this.filtros.proveedor_id}`);
                const json = await res.json();
                this.productos = json.data || [];
                this.seleccionados = this.productos.filter(p => p.requiere_compra && p.cantidad_sugerida > 0);
            } catch(e) {
                this.productos = [];
            }
        },
        get productosFiltrados() {
            if (this.filtros.solo_criticos === 'true') {
                return this.productos.filter(p => p.requiere_compra);
            }
            return this.productos;
        },
        get totalInversionEstimada() {
            return this.seleccionados.reduce((acc, p) => acc + (p.inversion_estimada || 0), 0);
        },
        toggleTodos(e) {
            if (e.target.checked) {
                this.seleccionados = this.productosFiltrados.filter(p => p.cantidad_sugerida > 0);
            } else {
                this.seleccionados = [];
            }
        },
        async generarOrdenCompra() {
            const proveedorId = this.filtros.proveedor_id || (this.seleccionados[0] ? this.seleccionados[0].proveedor_id : null);
            if (!proveedorId) {
                alert("Por favor seleccione un proveedor específico para emitir la Orden de Compra consolidada.");
                return;
            }
            if (!confirm(`¿Desea emitir una Orden de Compra con ${this.seleccionados.length} artículos por un monto de $${this.totalInversionEstimada.toFixed(2)}?`)) return;

            this.procesando = true;
            try {
                const res = await fetch('/api/compras/orden-automatica', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        proveedor_id: proveedorId,
                        items: this.seleccionados.map(p => ({
                            producto_id: p.producto_id,
                            cantidad_a_pedir: p.cantidad_sugerida,
                            costo_unitario_estimado: p.costo_unitario_estimado
                        }))
                    })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(json.message);
                    this.consultar();
                } else {
                    alert('Error: ' + json.message);
                }
            } catch (e) {
                alert('Error al procesar: ' + e.message);
            } finally {
                this.procesando = false;
            }
        }
    }
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>