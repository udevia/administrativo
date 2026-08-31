<?php
$pageTitle = 'Zonas y Rutas Geográficas - mi';
$activeMenu = 'maestros_zonas';
ob_start();
?>
<div class="space-y-4" x-data="maestroZonasApp()">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">Logística & Rutas</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Zonas y Rutas de Despacho</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Clasifique a los clientes por sectores, ciudades o rutas de preventa para optimizar entregas.</p>
        </div>

        <button @click="nuevaZona()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-map-pin"></i>
            <span>Nueva Zona</span>
        </button>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                    <th class="p-3">Código</th>
                    <th class="p-3">Descripción de la Zona</th>
                    <th class="p-3 text-center">Clientes Asignados</th>
                    <th class="p-3 text-center">Estado</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
                <template x-for="z in zonas" :key="z.id">
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="p-3 font-mono font-bold text-teal-600" x-text="z.codigo"></td>
                        <td class="p-3 font-bold text-slate-800" x-text="z.descripcion"></td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full font-mono font-bold bg-slate-100 text-slate-700" x-text="z.total_clientes || 0"></span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="z.estado == 1 ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-500'" x-text="z.estado == 1 ? 'Activa' : 'Inactiva'"></span>
                        </td>
                        <td class="p-3 text-center">
                            <button @click="editarZona(z)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Modal Formulario -->
    <div x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="modalAbierto = false">
            <h3 class="font-bold text-base text-slate-800" x-text="form.id ? 'Editar Zona' : 'Nueva Zona Geográfica'"></h3>
            <form @submit.prevent="guardar()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Código *</label>
                    <input type="text" x-model="form.codigo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-teal-600">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Descripción de la Zona / Sector *</label>
                    <input type="text" x-model="form.descripcion" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalAbierto = false" class="px-4 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-bold px-5 py-2.5 rounded-xl shadow">Guardar Zona</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function maestroZonasApp() {
    return {
        zonas: [],
        modalAbierto: false,
        form: { id: null, codigo: '', descripcion: '', estado: 1 },
        async init() {
            await this.cargarZonas();
        },
        async cargarZonas() {
            try {
                const res = await fetch('/api/maestros/zonas');
                const json = await res.json();
                if (json.status === 'success') this.zonas = json.data || [];
            } catch (e) {
                console.error(e);
            }
        },
        nuevaZona() {
            this.form = { id: null, codigo: 'ZON-' + Math.floor(10 + Math.random() * 90), descripcion: '', estado: 1 };
            this.modalAbierto = true;
        },
        editarZona(z) {
            this.form = JSON.parse(JSON.stringify(z));
            this.modalAbierto = true;
        },
        async guardar() {
            try {
                const res = await fetch('/api/maestros/zonas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalAbierto = false;
                    await this.cargarZonas();
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
