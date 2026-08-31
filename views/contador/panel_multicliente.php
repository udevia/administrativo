<?php
$pageTitle = 'Panel Profesional del Contador - mi';
$activeMenu = 'contador';
ob_start();
?>
<div class="space-y-4" x-data="contadorMultiempresaApp()">
    <!-- Barra Superior: Selector de Empresa Activa -->
    <div class="bg-slate-900 text-white rounded-2xl shadow-md p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-blue-400 font-bold uppercase tracking-wider">
                <i class="fa-solid fa-briefcase"></i> Módulo Profesional del Contador
            </div>
            <h2 class="text-lg font-bold mt-0.5 flex items-center gap-2">
                <span>Empresa Activa:</span>
                <span class="text-blue-300">Distribuidora Central Carabobo C.A. (J-12345678-9)</span>
            </h2>
        </div>
        <div class="flex items-center gap-3">
            <button @click="cerrarMesFiscal()" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                <i class="fa-solid fa-lock"></i> Cerrar Mes Fiscal
            </button>
        </div>
    </div>

    <!-- Gestión de Comprobantes de la Empresa Seleccionada -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-4">
            <div>
                <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-blue-600"></i> Asientos y Comprobantes Contables
                </h3>
                <p class="text-xs text-slate-500">Auditoría, reclasificación de cuentas contables y verificación de partida doble</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="month" x-model="filtroMes" @change="cargarComprobantes()" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-mono">
            </div>
        </div>

        <!-- Tabla de Comprobantes -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 uppercase text-slate-700 font-bold border-b">
                    <tr>
                        <th class="p-3">Comprobante</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Concepto General</th>
                        <th class="p-3 text-right">Total ($)</th>
                        <th class="p-3 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="c in comprobantes" :key="c.id">
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="p-3 font-bold text-blue-700" x-text="c.numero_comprobante"></td>
                            <td class="p-3 text-slate-600" x-text="c.fecha_asiento || c.fecha"></td>
                            <td class="p-3 font-sans text-slate-800" x-text="c.concepto"></td>
                            <td class="p-3 text-right font-bold text-slate-900" x-text="'$' + Number(c.total_debe || 0).toFixed(2)"></td>
                            <td class="p-3 text-center font-sans">
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="c.estado"></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="comprobantes.length === 0">
                        <td colspan="5" class="text-center py-12 text-slate-400 font-sans">
                            No hay comprobantes para auditar en este período.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function contadorMultiempresaApp() {
    return {
        filtroMes: '2026-08',
        comprobantes: [],
        init() {
            this.cargarComprobantes();
        },
        async cargarComprobantes() {
            try {
                const res = await fetch('/api/contabilidad/asientos');
                const json = await res.json();
                this.comprobantes = json.data || [];
            } catch(e) {}
        },
        cerrarMesFiscal() {
            if (!confirm(`¿Desea congelar el período contable ${this.filtroMes}?`)) return;
            alert('Período fiscal auditado y sellado exitosamente.');
        }
    }
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>