<?php
$pageTitle = 'Migración e Importación Masiva CSV - mi';
$activeMenu = 'migracion';
ob_start();
?>
<div class="space-y-4" x-data="migracionApp()">
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-file-excel text-emerald-600"></i> Asistente de Migración e Importación Masiva
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Alimenta la base de datos inicial de tu negocio desde plantillas CSV estándar</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="descargarPlantilla()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-download"></i> Descargar Plantilla CSV (<span x-text="entidadSeleccionada"></span>)
            </button>
        </div>
    </div>

    <!-- Paso 1: Selección de Módulo y Carga de Archivo -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase">1. Módulo a Importar</label>
            <select x-model="entidadSeleccionada" @change="reiniciarEstado()" class="w-full text-xs font-semibold border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                <option value="productos">📦 Catálogo de Productos y Stock Inicial</option>
                <option value="clientes">👥 Clientes y Condiciones Comerciales</option>
                <option value="proveedores">🚚 Proveedores y Contactos</option>
                <option value="categorias">📑 Categorías y Departamentos</option>
                <option value="almacenes">🏢 Almacenes y Depósitos</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase">2. Seleccionar Archivo (.CSV con cabecera)</label>
            <div class="flex gap-2">
                <input type="file" id="fileInput" @change="cargarArchivo($event)" accept=".csv, .txt" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1 bg-slate-50">
                <button @click="analizarArchivo()" :disabled="!archivoSeleccionado || analizando" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2 shrink-0">
                    <i class="fa-solid fa-eye" x-show="!analizando"></i>
                    <i class="fa-solid fa-spinner fa-spin" x-show="analizando"></i>
                    <span>Analizar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Errores de Estructura -->
    <div x-show="analisis.errores && analisis.errores.length > 0" class="bg-red-50 border border-red-200 rounded-2xl p-4" x-cloak>
        <h4 class="text-xs font-bold text-red-800 uppercase flex items-center gap-2 mb-2">
            <i class="fa-solid fa-circle-exclamation"></i> Se detectaron inconsistencias en el archivo:
        </h4>
        <ul class="list-disc list-inside text-xs text-red-700 space-y-1">
            <template x-for="err in analisis.errores" :key="err">
                <li x-text="err"></li>
            </template>
        </ul>
    </div>

    <!-- Paso 2: Vista Previa de Datos -->
    <div x-show="analisis.valido && analisis.vista_previa && analisis.vista_previa.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden" x-cloak>
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <span class="text-xs font-bold text-slate-700">
                <i class="fa-solid fa-table-list mr-1 text-blue-600"></i> Vista Previa (Primeros 10 de <span x-text="analisis.total_filas"></span> filas)
            </span>
            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full">
                <i class="fa-solid fa-check-circle mr-1"></i> Estructura Válida
            </span>
        </div>
        <div class="overflow-x-auto max-h-80">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-100 text-slate-600 font-bold uppercase border-b sticky top-0">
                    <tr>
                        <template x-for="col in analisis.headers" :key="col">
                            <th class="p-2.5 whitespace-nowrap" x-text="col"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white font-mono">
                    <template x-for="(fila, idx) in analisis.vista_previa" :key="idx">
                        <tr class="hover:bg-blue-50/40">
                            <template x-for="col in analisis.headers" :key="col">
                                <td class="p-2.5 whitespace-nowrap text-slate-700" x-text="fila[col] || '-'"></td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t flex justify-end gap-3">
            <button @click="reiniciarEstado()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">Cancelar</button>
            <button @click="procesarImportacionFinal()" :disabled="procesando" class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs px-6 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-cloud-arrow-up" x-show="!procesando"></i>
                <i class="fa-solid fa-spinner fa-spin" x-show="procesando"></i>
                <span>Importar Definitivamente <span x-text="analisis.total_filas"></span> Registros</span>
            </button>
        </div>
    </div>
</div>

<script>
function migracionApp() {
    return {
        entidadSeleccionada: 'productos',
        archivoSeleccionado: null,
        analizando: false,
        procesando: false,
        analisis: { valido: false, headers: [], vista_previa: [], total_filas: 0, errores: [] },
        descargarPlantilla() {
            window.location.href = `/api/migracion/plantilla/${this.entidadSeleccionada}`;
        },
        cargarArchivo(e) {
            this.archivoSeleccionado = e.target.files[0] || null;
            this.analisis = { valido: false, headers: [], vista_previa: [], total_filas: 0, errores: [] };
        },
        reiniciarEstado() {
            this.archivoSeleccionado = null;
            const input = document.getElementById('fileInput');
            if (input) input.value = '';
            this.analisis = { valido: false, headers: [], vista_previa: [], total_filas: 0, errores: [] };
        },
        async analizarArchivo() {
            if (!this.archivoSeleccionado) return;
            this.analizando = true;
            const formData = new FormData();
            formData.append('entidad', this.entidadSeleccionada);
            formData.append('archivo', this.archivoSeleccionado);
            try {
                const res = await fetch('/api/migracion/analizar', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.analisis = json.data;
                } else {
                    alert('Error en el archivo: ' + json.message);
                }
            } catch (e) {
                alert('Error al comunicar con el servidor: ' + e.message);
            } finally {
                this.analizando = false;
            }
        },
        async procesarImportacionFinal() {
            if (!confirm(`¿Está seguro de procesar la carga masiva de ${this.analisis.total_filas} registros en ${this.entidadSeleccionada}?`)) return;
            this.procesando = true;
            try {
                const res = await fetch('/api/migracion/ejecutar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        entidad: this.entidadSeleccionada,
                        filas: this.analisis.filas_totales
                    })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(json.mensaje || 'Importación completada exitosamente.');
                    this.reiniciarEstado();
                } else {
                    alert('Error durante la importación: ' + json.message);
                }
            } catch (e) {
                alert('Error en la transacción: ' + e.message);
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