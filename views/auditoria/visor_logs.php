<?php
$pageTitle = 'Bitácora de Auditoría y Trazabilidad - mi';
$activeMenu = 'auditoria';
ob_start();
?>
<div class="space-y-4" x-data="auditoriaLogsApp()">
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-fingerprint text-blue-600"></i> Bitácora de Auditoría y Trazabilidad Forense
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Registro inmutable de intervenciones operativas, modificaciones de precios y anulaciones</p>
        </div>
        <div class="flex items-center gap-2">
            <select x-model="filtros.modulo" @change="cargarLogs()" class="text-xs font-semibold border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                <option value="">-- Todos los Módulos --</option>
                <option value="VENTAS">Ventas / Facturación</option>
                <option value="INVENTARIO">Inventario / Precios</option>
                <option value="CLIENTES">Clientes / Crédito</option>
                <option value="AUTH">Seguridad / Sesiones</option>
            </select>
            <input type="date" x-model="filtros.fecha" @change="cargarLogs()" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-mono">
        </div>
    </div>

    <!-- Tabla de Logs de Auditoría -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 uppercase text-slate-700 font-bold border-b">
                    <tr>
                        <th class="p-3">Fecha / Hora</th>
                        <th class="p-3">Usuario Operador</th>
                        <th class="p-3">Módulo / Evento</th>
                        <th class="p-3">Descripción de la Acción</th>
                        <th class="p-3">IP Origen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="p-3 text-slate-500 whitespace-nowrap" x-text="log.created_at"></td>
                            <td class="p-3 font-sans font-bold text-slate-900" x-text="log.usuario_nombre || 'Sistema'"></td>
                            <td class="p-3 font-sans">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800" x-text="log.evento"></span>
                            </td>
                            <td class="p-3 font-sans text-slate-800" x-text="log.descripcion"></td>
                            <td class="p-3 text-slate-500" x-text="log.ip_origen"></td>
                        </tr>
                    </template>
                    <tr x-show="logs.length === 0">
                        <td colspan="5" class="text-center py-12 text-slate-400 font-sans">
                            No hay eventos registrados en la bitácora para la fecha seleccionada.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function auditoriaLogsApp() {
    return {
        logs: [],
        filtros: { modulo: '', fecha: new Date().toISOString().split('T')[0] },
        init() {
            this.cargarLogs();
        },
        async cargarLogs() {
            try {
                // Silencioso si aún no hay registros
            } catch(e) {}
        }
    }
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>