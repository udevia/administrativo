<?php
$pageTitle = 'Vendedores y Comisiones - mi';
$activeMenu = 'maestros_vendedores';
ob_start();
?>
<div class="space-y-4" x-data="maestroVendedoresApp()">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-700 font-bold text-xs">Fuerza de Ventas</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Vendedores y Comisiones</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Configure asesores comerciales, % de comisión por facturación y cobranza.</p>
        </div>

        <button @click="nuevoVendedor()" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-user-plus"></i>
            <span>Nuevo Vendedor</span>
        </button>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                    <th class="p-3">Código</th>
                    <th class="p-3">Nombre Asesor</th>
                    <th class="p-3">Cédula / RIF</th>
                    <th class="p-3">Contacto</th>
                    <th class="p-3 text-center">% Comisión Ventas</th>
                    <th class="p-3 text-center">% Comisión Cobros</th>
                    <th class="p-3 text-center">Estado</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
                <template x-for="v in vendedores" :key="v.id">
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="p-3 font-mono font-bold text-cyan-600" x-text="v.codigo"></td>
                        <td class="p-3 font-bold text-slate-800" x-text="v.nombre"></td>
                        <td class="p-3 font-mono text-slate-700" x-text="v.cedula_rif"></td>
                        <td class="p-3" x-text="v.telefono || v.email || 'N/A'"></td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded font-mono font-bold bg-blue-50 text-blue-700" x-text="parseFloat(v.comision_ventas || 0).toFixed(2) + '%'"></span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded font-mono font-bold bg-emerald-50 text-emerald-700" x-text="parseFloat(v.comision_cobros || 0).toFixed(2) + '%'"></span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="v.estado == 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="v.estado == 1 ? 'Activo' : 'Inactivo'"></span>
                        </td>
                        <td class="p-3 text-center">
                            <button @click="editarVendedor(v)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Modal Formulario -->
    <div x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="modalAbierto = false">
            <h3 class="font-bold text-base text-slate-800" x-text="form.id ? 'Editar Vendedor' : 'Nuevo Asesor Comercial'"></h3>
            <form @submit.prevent="guardar()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Código *</label>
                    <input type="text" x-model="form.codigo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-cyan-600">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nombre Completo *</label>
                    <input type="text" x-model="form.nombre" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Cédula / Documento Fiscal *</label>
                    <input type="text" x-model="form.cedula_rif" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Teléfono</label>
                        <input type="text" x-model="form.telefono" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email</label>
                        <input type="email" x-model="form.email" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">% Comisión Venta</label>
                        <input type="number" step="0.01" x-model="form.comision_ventas" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">% Comisión Cobro</label>
                        <input type="number" step="0.01" x-model="form.comision_cobros" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalAbierto = false" class="px-4 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold px-5 py-2.5 rounded-xl shadow">Guardar Vendedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function maestroVendedoresApp() {
    return {
        vendedores: [],
        modalAbierto: false,
        form: { id: null, codigo: '', nombre: '', cedula_rif: '', telefono: '', email: '', comision_ventas: 0, comision_cobros: 0, estado: 1 },
        async init() {
            await this.cargarVendedores();
        },
        async cargarVendedores() {
            try {
                const res = await fetch('/api/maestros/vendedores');
                const json = await res.json();
                if (json.status === 'success') this.vendedores = json.data || [];
            } catch (e) {
                console.error(e);
            }
        },
        nuevoVendedor() {
            this.form = { id: null, codigo: 'VEN-' + Math.floor(10 + Math.random() * 90), nombre: '', cedula_rif: '', telefono: '', email: '', comision_ventas: 2.5, comision_cobros: 1.0, estado: 1 };
            this.modalAbierto = true;
        },
        editarVendedor(v) {
            this.form = JSON.parse(JSON.stringify(v));
            this.modalAbierto = true;
        },
        async guardar() {
            try {
                const res = await fetch('/api/maestros/vendedores', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalAbierto = false;
                    await this.cargarVendedores();
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
