<?php
$pageTitle = 'Departamentos y Categorías - mi';
$activeMenu = 'maestros_departamentos';
ob_start();
?>
<div class="space-y-4" x-data="maestroDepartamentosApp()">
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 font-bold text-xs">Clasificación</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Departamentos y Categorías</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Estructura jerárquica de agrupación de productos para estadísticas de venta y reportes.</p>
        </div>

        <div class="flex items-center gap-2">
            <button @click="nuevoDepto()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo Departamento</span>
            </button>
            <button @click="nuevaCategoria()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-folder-plus"></i>
                <span>Nueva Categoría</span>
            </button>
        </div>
    </div>

    <!-- Grid: Departamentos a la izquierda y Categorías a la derecha -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Departamentos -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 flex justify-between items-center">
                <span>Departamentos Maestros</span>
                <span class="text-[11px] font-mono text-slate-400" x-text="departamentos.length + ' registros'"></span>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                <template x-for="d in departamentos" :key="d.id">
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                <i class="fa-solid fa-sitemap"></i>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800" x-text="d.descripcion"></span>
                                <span class="font-mono text-[10px] text-slate-400 block" x-text="d.codigo"></span>
                            </div>
                        </div>
                        <button @click="editarDepto(d)" class="p-1 text-slate-400 hover:text-purple-600"><i class="fa-solid fa-pen"></i></button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Categorías -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 flex justify-between items-center">
                <span>Categorías de Artículos</span>
                <span class="text-[11px] font-mono text-slate-400" x-text="categorias.length + ' registros'"></span>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                <template x-for="c in categorias" :key="c.id">
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                <i class="fa-solid fa-folder-tree"></i>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800" x-text="c.descripcion"></span>
                                <span class="text-[10px] text-blue-500 font-medium block" x-text="'Depto: ' + (c.depto_nombre || 'General')"></span>
                            </div>
                        </div>
                        <button @click="editarCategoria(c)" class="p-1 text-slate-400 hover:text-blue-600"><i class="fa-solid fa-pen"></i></button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Departamento -->
    <div x-show="modalDepto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="modalDepto = false">
            <h3 class="font-bold text-base text-slate-800" x-text="formDepto.id ? 'Editar Departamento' : 'Nuevo Departamento'"></h3>
            <form @submit.prevent="guardarDepto()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Código *</label>
                    <input type="text" x-model="formDepto.codigo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Descripción *</label>
                    <input type="text" x-model="formDepto.descripcion" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modalDepto = false" class="px-4 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="bg-purple-600 text-white font-bold px-5 py-2.5 rounded-xl shadow">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Categoría -->
    <div x-show="modalCat" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="modalCat = false">
            <h3 class="font-bold text-base text-slate-800" x-text="formCat.id ? 'Editar Categoría' : 'Nueva Categoría'"></h3>
            <form @submit.prevent="guardarCat()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Departamento Padre *</label>
                    <select x-model="formCat.departamento_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                        <template x-for="d in departamentos" :key="d.id">
                            <option :value="d.id" x-text="d.descripcion"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Código *</label>
                    <input type="text" x-model="formCat.codigo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Descripción de la Categoría *</label>
                    <input type="text" x-model="formCat.descripcion" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modalCat = false" class="px-4 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="bg-blue-600 text-white font-bold px-5 py-2.5 rounded-xl shadow">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function maestroDepartamentosApp() {
    return {
        departamentos: [],
        categorias: [],
        modalDepto: false,
        modalCat: false,
        formDepto: { id: null, codigo: '', descripcion: '', estado: 1 },
        formCat: { id: null, departamento_id: 1, codigo: '', descripcion: '', estado: 1 },
        async init() {
            await this.cargarDatos();
        },
        async cargarDatos() {
            try {
                const res = await fetch('/api/maestros/departamentos');
                const json = await res.json();
                if (json.status === 'success') {
                    this.departamentos = json.departamentos || [];
                    this.categorias = json.categorias || [];
                }
            } catch (e) {
                console.error(e);
            }
        },
        nuevoDepto() {
            this.formDepto = { id: null, codigo: 'DEP-' + Math.floor(10 + Math.random() * 90), descripcion: '', estado: 1 };
            this.modalDepto = true;
        },
        editarDepto(d) {
            this.formDepto = JSON.parse(JSON.stringify(d));
            this.modalDepto = true;
        },
        async guardarDepto() {
            try {
                const res = await fetch('/api/maestros/departamentos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formDepto)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalDepto = false;
                    await this.cargarDatos();
                }
            } catch (e) {
                alert(e.message);
            }
        },
        nuevaCategoria() {
            this.formCat = { id: null, departamento_id: this.departamentos[0]?.id || 1, codigo: 'CAT-' + Math.floor(10 + Math.random() * 90), descripcion: '', estado: 1 };
            this.modalCat = true;
        },
        editarCategoria(c) {
            this.formCat = JSON.parse(JSON.stringify(c));
            this.modalCat = true;
        },
        async guardarCat() {
            try {
                const res = await fetch('/api/maestros/categorias', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formCat)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalCat = false;
                    await this.cargarDatos();
                }
            } catch (e) {
                alert(e.message);
            }
        }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
