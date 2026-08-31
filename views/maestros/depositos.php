<?php
$pageTitle = 'Depósitos y Almacenes - mi';
$activeMenu = 'maestros_depositos';
ob_start();
?>
<div class="space-y-4" x-data="maestroDepositosApp()">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 font-bold text-xs">Inventario Físico</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Depósitos y Almacenes</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Defina almacenes físicos, tiendas, sucursales y ubicaciones de inventario.</p>
        </div>

        <button @click="nuevoDeposito()" class="bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-warehouse"></i>
            <span>Nuevo Depósito</span>
        </button>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                    <th class="p-3">Código</th>
                    <th class="p-3">Nombre / Descripción</th>
                    <th class="p-3">Responsable / Encargado</th>
                    <th class="p-3 text-center">Estado</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
                <template x-for="d in depositos" :key="d.id">
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="p-3 font-mono font-bold text-orange-600" x-text="d.codigo"></td>
                        <td class="p-3 font-bold text-slate-800" x-text="d.descripcion"></td>
                        <td class="p-3 text-slate-600" x-text="d.responsable || 'Sin asignar'"></td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="d.estado == 1 ? 'bg-orange-50 text-orange-700' : 'bg-slate-100 text-slate-500'" x-text="d.estado == 1 ? 'Activo' : 'Inactivo'"></span>
                        </td>
                        <td class="p-3 text-center">
                            <button @click="editarDeposito(d)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Modal Formulario -->
    <div x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="modalAbierto = false">
            <h3 class="font-bold text-base text-slate-800" x-text="form.id ? 'Editar Depósito' : 'Nuevo Depósito'"></h3>
            <form @submit.prevent="guardar()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Código *</label>
                    <input type="text" x-model="form.codigo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-orange-600">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Descripción del Depósito *</label>
                    <input type="text" x-model="form.descripcion" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Responsable del Almacén</label>
                    <input type="text" x-model="form.responsable" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalAbierto = false" class="px-4 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold px-5 py-2.5 rounded-xl shadow">Guardar Depósito</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function maestroDepositosApp() {
    return {
        depositos: [],
        modalAbierto: false,
        form: { id: null, codigo: '', descripcion: '', responsable: '', estado: 1 },
        async init() {
            await this.cargarDepositos();
        },
        async cargarDepositos() {
            try {
                const res = await fetch('/api/maestros/depositos');
                const json = await res.json();
                if (json.status === 'success') this.depositos = json.data || [];
            } catch (e) {
                console.error(e);
            }
        },
        nuevoDeposito() {
            this.form = { id: null, codigo: 'DEP-' + Math.floor(10 + Math.random() * 90), descripcion: '', responsable: '', estado: 1 };
            this.modalAbierto = true;
        },
        editarDeposito(d) {
            this.form = JSON.parse(JSON.stringify(d));
            this.modalAbierto = true;
        },
        async guardar() {
            try {
                const res = await fetch('/api/maestros/depositos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalAbierto = false;
                    await this.cargarDepositos();
                } else {
                    alert(json.mensaje);
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
