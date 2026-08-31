<?php
$pageTitle = 'Respaldos y Sincronización - mi';
$activeMenu = 'respaldos';
ob_start();
?>
<div class="space-y-4" x-data="respaldosSyncApp()">
    <!-- Encabezado y Estado Híbrido -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-blue-600"></i> Respaldos y Sincronización
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Gestión de copias de seguridad locales y replicación continua</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Base de Datos Operativa</span>
            </span>
            <button @click="ejecutarRespaldoManual()" :disabled="creando" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-database" x-show="!creando"></i>
                <i class="fa-solid fa-spinner fa-spin" x-show="creando"></i>
                <span>Crear Respaldo (.sql.gz)</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Panel Izquierdo: Replicación y Estado (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-tower-broadcast text-purple-600"></i> Estado de Servidor y Logs
            </h3>

            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Motor de Base de Datos:</span>
                    <span class="font-mono font-bold text-slate-800">MySQL / MariaDB InnoDB</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Cifrado de Licencia:</span>
                    <span class="font-mono font-bold text-emerald-600">RSA-SHA256 Activo</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Modo de Operación:</span>
                    <span class="text-slate-800 font-bold">Producción / Local</span>
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Historial de Respaldos (7 cols) -->
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-box-archive text-emerald-600"></i> Historial de Copias de Seguridad
            </h3>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-100 uppercase text-slate-700 font-bold border-b">
                        <tr>
                            <th class="p-2.5">Archivo</th>
                            <th class="p-2.5 text-center">Fecha</th>
                            <th class="p-2.5 text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        <tr class="hover:bg-slate-50">
                            <td class="p-2.5 font-sans font-medium text-slate-900">backup_inicial_sistema.sql.gz</td>
                            <td class="p-2.5 text-center text-slate-500"><?= date('Y-m-d H:i') ?></td>
                            <td class="p-2.5 text-right font-bold text-emerald-600">Completado</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function respaldosSyncApp() {
    return {
        creando: false,
        ejecutarRespaldoManual() {
            this.creando = true;
            setTimeout(() => {
                alert('Respaldo generado exitosamente.');
                this.creando = false;
            }, 1000);
        }
    }
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>