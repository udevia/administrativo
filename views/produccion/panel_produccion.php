<?php
$pageTitle = 'Producción y Ensambles (BOM) - mi ERP';
$activeMenu = 'produccion';
ob_start();
?>
<div class="space-y-4" x-data="produccionApp()" x-cloak>
    <!-- Encabezado + Tabs -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-industry text-blue-600"></i> Producción y Ensambles (BOM)
                </h1>
                <p class="text-xs text-slate-500 mt-1">Gestión de Fórmulas (BOM) y Órdenes de Fabricación con costeo real (MP + MO + CIF).</p>
            </div>
            <div class="flex gap-2">
                <button x-show="tabActivo === 'formulas'" @click="abrirNuevoFormula()"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Nueva Fórmula BOM
                </button>
                <button x-show="tabActivo === 'ordenes'" @click="abrirNuevaOrden()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Nueva Orden de Fabricación
                </button>
            </div>
        </div>
        <!-- Tabs -->
        <div class="flex gap-1 bg-slate-100 p-1 rounded-xl w-fit">
            <button @click="tabActivo='formulas'" :class="tabActivo==='formulas'?'bg-white shadow-sm text-slate-800 font-bold':'text-slate-500 hover:text-slate-700'"
                    class="text-xs px-4 py-2 rounded-lg transition font-medium">
                <i class="fa-solid fa-flask mr-1"></i> Fórmulas BOM
            </button>
            <button @click="tabActivo='ordenes'" :class="tabActivo==='ordenes'?'bg-white shadow-sm text-slate-800 font-bold':'text-slate-500 hover:text-slate-700'"
                    class="text-xs px-4 py-2 rounded-lg transition font-medium">
                <i class="fa-solid fa-gears mr-1"></i> Órdenes de Fabricación
            </button>
        </div>
    </div>

    <!-- ===== TAB FORMULAS BOM ===== -->
    <div x-show="tabActivo === 'formulas'" class="space-y-3">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b flex justify-between items-center">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-flask text-blue-600"></i> Fórmulas / Plantillas de Producción
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-100 text-slate-600 uppercase font-bold border-b text-[11px]">
                        <tr>
                            <th class="p-3">Nombre Fórmula</th>
                            <th class="p-3">Producto Terminado</th>
                            <th class="p-3 text-center">Rend. x Lote</th>
                            <th class="p-3 text-center"># Materiales</th>
                            <th class="p-3 text-right">Costo Est. ($)</th>
                            <th class="p-3 text-center">Estado</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        <template x-for="f in formulas" :key="f.id">
                            <tr class="hover:bg-blue-50/40 transition">
                                <td class="p-3 font-bold text-blue-700" x-text="f.nombre_formula"></td>
                                <td class="p-3 font-sans font-medium text-slate-900" x-text="f.producto_terminado_nombre"></td>
                                <td class="p-3 text-center" x-text="f.rendimiento_lote"></td>
                                <td class="p-3 text-center font-bold" x-text="f.num_materiales || '—'"></td>
                                <td class="p-3 text-right font-bold text-emerald-700" x-text="'$' + fmt(f.costo_estimado)"></td>
                                <td class="p-3 text-center">
                                    <span :class="f.activa ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                          class="text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="f.activa ? 'ACTIVA' : 'INACTIVA'"></span>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="verFormula(f)"
                                                class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-blue-200 transition">
                                            <i class="fa-solid fa-eye"></i> Ver
                                        </button>
                                        <button @click="usarFormula(f)"
                                                class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-indigo-200 transition">
                                            <i class="fa-solid fa-gears"></i> Fabricar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="formulas.length === 0">
                            <td colspan="7" class="text-center py-16 text-slate-400 font-sans">
                                <i class="fa-solid fa-flask text-4xl block mb-2 text-slate-300"></i>
                                No hay fórmulas registradas. Cree la primera Fórmula BOM.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== TAB ORDENES ===== -->
    <div x-show="tabActivo === 'ordenes'" class="space-y-3">
        <!-- KPIs Ordenes -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <p class="text-xs text-slate-500">Órdenes Total</p>
                <p class="text-2xl font-black font-mono text-slate-800 mt-1" x-text="ordenes.length"></p>
            </div>
            <div class="bg-white rounded-2xl border border-amber-100 p-4">
                <p class="text-xs text-slate-500">En Proceso</p>
                <p class="text-2xl font-black font-mono text-amber-600 mt-1" x-text="ordenes.filter(o=>o.estado==='EN_PROCESO').length"></p>
            </div>
            <div class="bg-white rounded-2xl border border-emerald-100 p-4">
                <p class="text-xs text-slate-500">Completadas</p>
                <p class="text-2xl font-black font-mono text-emerald-700 mt-1" x-text="ordenes.filter(o=>o.estado==='COMPLETADA').length"></p>
            </div>
            <div class="bg-white rounded-2xl border border-blue-100 p-4">
                <p class="text-xs text-slate-500">Costo Total Producido</p>
                <p class="text-xl font-black font-mono text-blue-700 mt-1" x-text="'$' + fmt(ordenes.reduce((s,o)=>s+Number(o.costo_total_materia_prima||0),0))"></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-gears text-indigo-600"></i> Órdenes de Fabricación
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-100 text-slate-600 uppercase font-bold border-b text-[11px]">
                        <tr>
                            <th class="p-3">N° Orden</th>
                            <th class="p-3">Fórmula / Producto</th>
                            <th class="p-3 text-center">Cant. Planificada</th>
                            <th class="p-3 text-center">Cant. Fabricada</th>
                            <th class="p-3 text-right">Costo Total ($)</th>
                            <th class="p-3 text-right text-emerald-700">Costo Unit. ($)</th>
                            <th class="p-3 text-center">Estado</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        <template x-for="op in ordenes" :key="op.id">
                            <tr class="hover:bg-indigo-50/40 transition">
                                <td class="p-3 font-bold text-indigo-700" x-text="op.numero_orden"></td>
                                <td class="p-3 font-sans font-medium text-slate-900" x-text="op.formula_nombre || '—'"></td>
                                <td class="p-3 text-center" x-text="Number(op.cantidad_planificada).toFixed(2)"></td>
                                <td class="p-3 text-center font-bold" x-text="Number(op.cantidad_fabricada_real||0).toFixed(2)"></td>
                                <td class="p-3 text-right font-bold" x-text="'$' + fmt(op.costo_total_materia_prima)"></td>
                                <td class="p-3 text-right font-bold text-emerald-800" x-text="'$' + fmt(op.costo_unitario_terminado)"></td>
                                <td class="p-3 text-center">
                                    <span :class="{
                                        'bg-amber-100 text-amber-700': op.estado==='EN_PROCESO',
                                        'bg-emerald-100 text-emerald-700': op.estado==='COMPLETADA',
                                        'bg-blue-100 text-blue-700': op.estado==='PLANIFICADA',
                                        'bg-red-100 text-red-600': op.estado==='CANCELADA'
                                    }" class="text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="op.estado"></span>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button x-show="op.estado === 'PLANIFICADA' || op.estado === 'EN_PROCESO'"
                                                @click="completarOrden(op)"
                                                class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-emerald-200 transition">
                                            <i class="fa-solid fa-check"></i> Completar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="ordenes.length === 0">
                            <td colspan="8" class="text-center py-16 text-slate-400 font-sans">
                                <i class="fa-solid fa-gears text-4xl block mb-2 text-slate-300"></i>
                                No hay órdenes de producción registradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======= MODAL NUEVA FORMULA BOM ======= -->
    <div x-show="modalFormula" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden flex flex-col max-h-[90vh]">
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white p-5 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-flask"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold">Nueva Fórmula / Plantilla BOM</h2>
                        <p class="text-[10px] text-blue-200">Define los materiales y su consumo por lote de producción</p>
                    </div>
                </div>
                <button @click="modalFormula = false" class="text-white/60 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6 space-y-4 text-xs overflow-y-auto flex-1">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1.5">Nombre de la Fórmula *</label>
                        <input type="text" x-model="formulaForm.nombre_formula" placeholder="Ej: BOM-Jabón Lavanda 250ml"
                               class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1.5">Producto Terminado *</label>
                        <select x-model.number="formulaForm.producto_terminado_id" class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
                            <option value="">— Seleccione producto —</option>
                            <template x-for="p in productos" :key="p.id">
                                <option :value="p.id" x-text="p.codigo + ' - ' + p.descripcion"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1.5">Rendimiento x Lote *</label>
                        <input type="number" min="0.01" step="0.01" x-model.number="formulaForm.rendimiento_lote" placeholder="Ej: 100 (unidades por lote)"
                               class="w-full border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <!-- Materiales -->
                <div class="border-t border-slate-100 pt-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold text-slate-700">Materiales / Ingredientes</h4>
                        <button @click="agregarMaterial()" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs px-3 py-1.5 rounded-lg border border-blue-200 transition">
                            <i class="fa-solid fa-plus mr-1"></i>Agregar Material
                        </button>
                    </div>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-[11px]">
                            <thead class="bg-slate-100 font-bold text-slate-700">
                                <tr>
                                    <th class="p-2 text-left">Material / Insumo</th>
                                    <th class="p-2 text-center w-28">Cantidad x Lote</th>
                                    <th class="p-2 text-center w-24">Unidad</th>
                                    <th class="p-2 text-right w-28">Costo Est. ($)</th>
                                    <th class="p-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(mat, idx) in formulaForm.materiales" :key="idx">
                                    <tr>
                                        <td class="p-2">
                                            <select x-model.number="mat.material_id" class="w-full border border-slate-200 rounded-lg p-1.5 focus:outline-none focus:border-blue-500">
                                                <option value="">— Seleccione —</option>
                                                <template x-for="p in productos" :key="p.id">
                                                    <option :value="p.id" x-text="p.codigo + ' - ' + p.descripcion"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="p-2 text-center">
                                            <input type="number" min="0.001" step="0.001" x-model.number="mat.cantidad_requerida"
                                                   class="w-24 border border-slate-200 rounded-lg p-1.5 text-center font-mono focus:outline-none focus:border-blue-500">
                                        </td>
                                        <td class="p-2 text-center">
                                            <input type="text" x-model="mat.unidad" placeholder="kg/L/Unid"
                                                   class="w-20 border border-slate-200 rounded-lg p-1.5 text-center focus:outline-none focus:border-blue-500">
                                        </td>
                                        <td class="p-2 text-right">
                                            <input type="number" min="0" step="0.01" x-model.number="mat.costo_estimado"
                                                   class="w-24 border border-slate-200 rounded-lg p-1.5 text-right font-mono font-bold text-emerald-700 focus:outline-none focus:border-blue-500">
                                        </td>
                                        <td class="p-2 text-center">
                                            <button @click="formulaForm.materiales.splice(idx, 1)" class="text-red-400 hover:text-red-600">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t border-slate-200" x-show="formulaForm.materiales.length > 0">
                                <tr>
                                    <td colspan="3" class="p-2 text-right font-bold text-slate-700">Costo Total Estimado x Lote:</td>
                                    <td class="p-2 text-right font-black font-mono text-emerald-700"
                                        x-text="'$' + fmt(formulaForm.materiales.reduce((s,m)=>s+Number(m.costo_estimado||0),0))"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                    <button @click="modalFormula = false" class="px-4 py-2 text-slate-500 font-bold hover:text-slate-800">Cancelar</button>
                    <button @click="guardarFormula()"
                            :disabled="guardando || !formulaForm.nombre_formula || !formulaForm.producto_terminado_id || formulaForm.materiales.length === 0"
                            class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold px-6 py-2.5 rounded-xl transition flex items-center gap-2">
                        <i class="fa-solid fa-flask"></i> Guardar Fórmula
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ======= MODAL NUEVA ORDEN FABRICACION ======= -->
    <div x-show="modalOrden" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold">Nueva Orden de Fabricación</h2>
                        <p class="text-[10px] text-indigo-200">Seleccione la fórmula y la cantidad a producir</p>
                    </div>
                </div>
                <button @click="modalOrden = false" class="text-white/60 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-600 mb-1.5">Fórmula BOM *</label>
                    <select x-model.number="ordenForm.formula_id" @change="onFormulaChange()" class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-indigo-500">
                        <option value="">— Seleccione fórmula —</option>
                        <template x-for="f in formulas" :key="f.id">
                            <option :value="f.id" x-text="f.nombre_formula + ' (Rend: ' + f.rendimiento_lote + ')'"></option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1.5">Cantidad a Fabricar *</label>
                        <input type="number" min="1" step="0.01" x-model.number="ordenForm.cantidad_planificada"
                               class="w-full border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1.5">Depósito Destino</label>
                        <select x-model.number="ordenForm.deposito_id" class="w-full border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-indigo-500">
                            <template x-for="d in depositos" :key="d.id">
                                <option :value="d.id" x-text="d.descripcion"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div x-show="formulaSeleccionada">
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 text-[11px]">
                        <p class="font-bold text-indigo-800 mb-2">Resumen de Consumo Estimado:</p>
                        <template x-for="mat in (formulaSeleccionada?.materiales || [])" :key="mat.id">
                            <div class="flex justify-between items-center py-1 border-b border-indigo-100 last:border-0">
                                <span class="text-slate-700" x-text="mat.descripcion || mat.material_nombre"></span>
                                <span class="font-mono font-bold text-indigo-700" x-text="fmt(mat.cantidad_requerida * ordenForm.cantidad_planificada) + ' ' + (mat.unidad || '')"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                    <button @click="modalOrden = false" class="px-4 py-2 text-slate-500 font-bold hover:text-slate-800">Cancelar</button>
                    <button @click="iniciarOrden()"
                            :disabled="guardando || !ordenForm.formula_id || !ordenForm.cantidad_planificada"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold px-6 py-2.5 rounded-xl transition flex items-center gap-2">
                        <i class="fa-solid fa-gears"></i> Iniciar Fabricación
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function produccionApp() {
    return {
        tabActivo: 'formulas',
        formulas: [],
        ordenes: [],
        productos: [],
        depositos: [],
        guardando: false,
        modalFormula: false,
        modalOrden: false,
        formulaSeleccionada: null,
        formulaForm: {
            nombre_formula: '',
            producto_terminado_id: '',
            rendimiento_lote: 1,
            materiales: []
        },
        ordenForm: {
            formula_id: '',
            cantidad_planificada: 1,
            deposito_id: 1
        },

        async init() {
            await Promise.all([
                this.cargarFormulas(),
                this.cargarOrdenes(),
                this.cargarProductos(),
                this.cargarDepositos()
            ]);
        },

        async cargarFormulas() {
            try {
                const r = await fetch('/api/produccion/formulas');
                const j = await r.json();
                this.formulas = j.data || [];
            } catch(e) { this.formulas = []; }
        },

        async cargarOrdenes() {
            try {
                const r = await fetch('/api/produccion/ordenes');
                const j = await r.json();
                this.ordenes = j.data || [];
            } catch(e) { this.ordenes = []; }
        },

        async cargarProductos() {
            try {
                const r = await fetch('/api/maestros/productos?limit=2000');
                const j = await r.json();
                this.productos = j.data || [];
            } catch(e) {}
        },

        async cargarDepositos() {
            try {
                const r = await fetch('/api/inventario/depositos');
                const j = await r.json();
                this.depositos = j.data || [{ id: 1, descripcion: 'Depósito Principal' }];
            } catch(e) { this.depositos = [{ id: 1, descripcion: 'Depósito Principal' }]; }
        },

        abrirNuevoFormula() {
            this.formulaForm = { nombre_formula: '', producto_terminado_id: '', rendimiento_lote: 1, materiales: [] };
            this.modalFormula = true;
        },

        abrirNuevaOrden() {
            this.ordenForm = { formula_id: '', cantidad_planificada: 1, deposito_id: this.depositos[0]?.id || 1 };
            this.formulaSeleccionada = null;
            this.modalOrden = true;
        },

        agregarMaterial() {
            this.formulaForm.materiales.push({ material_id: '', cantidad_requerida: 1, unidad: 'Unid', costo_estimado: 0 });
        },

        onFormulaChange() {
            this.formulaSeleccionada = this.formulas.find(f => f.id === this.ordenForm.formula_id) || null;
        },

        verFormula(f) {
            this.formulaSeleccionada = f;
            this.ordenForm.formula_id = f.id;
            this.modalOrden = true;
        },

        usarFormula(f) {
            this.ordenForm = { formula_id: f.id, cantidad_planificada: 1, deposito_id: this.depositos[0]?.id || 1 };
            this.formulaSeleccionada = f;
            this.modalOrden = true;
        },

        async guardarFormula() {
            if (!this.formulaForm.nombre_formula || !this.formulaForm.producto_terminado_id || this.formulaForm.materiales.length === 0) return;
            this.guardando = true;
            try {
                const r = await fetch('/api/produccion/formulas', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formulaForm)
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Fórmula BOM guardada exitosamente.');
                    this.modalFormula = false;
                    await this.cargarFormulas();
                } else { alert('❌ ' + j.message); }
            } catch(e) { alert('Error: ' + e.message); }
            finally { this.guardando = false; }
        },

        async iniciarOrden() {
            if (!this.ordenForm.formula_id || !this.ordenForm.cantidad_planificada) return;
            this.guardando = true;
            try {
                const r = await fetch('/api/produccion/orden', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...this.ordenForm, usuario_id: 1 })
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Orden de Fabricación iniciada. N°: ' + (j.data?.numero_orden || ''));
                    this.modalOrden = false;
                    this.tabActivo = 'ordenes';
                    await this.cargarOrdenes();
                } else { alert('❌ ' + j.message); }
            } catch(e) { alert('Error: ' + e.message); }
            finally { this.guardando = false; }
        },

        async completarOrden(op) {
            const cantFabricada = prompt(`Confirmar completado de la Orden ${op.numero_orden}.\nIngrese la cantidad fabricada real:`, op.cantidad_planificada);
            if (!cantFabricada) return;
            try {
                const r = await fetch('/api/produccion/completar', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ orden_id: op.id, cantidad_fabricada_real: Number(cantFabricada) })
                });
                const j = await r.json();
                if (j.status === 'success') {
                    alert('✅ Orden completada. Inventario actualizado con el producto terminado.');
                    await this.cargarOrdenes();
                } else { alert('❌ ' + j.message); }
            } catch(e) { alert('Error: ' + e.message); }
        },

        fmt(val) { return Number(val||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>