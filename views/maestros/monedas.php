<?php
$pageTitle = 'Monedas y Tasas de Cambio - mi';
$activeMenu = 'maestros_monedas';
ob_start();
?>
<div class="space-y-4" x-data="maestroMonedasApp()">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 font-bold text-xs">Multimoneda & BCV</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Monedas y Tasas de Cambio</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Gestione monedas del sistema (USD, VES, EUR), actualice la tasa diaria de cambio y consulte el histórico.</p>
        </div>

        <button @click="modalTasa = true" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-arrows-rotate"></i>
            <span>Actualizar Tasa del Día</span>
        </button>
    </div>

    <!-- Grid de Monedas y Tasas Actuales -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <template x-for="m in monedas" :key="m.id">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-3 relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-lg font-black" :class="m.es_base == 1 ? 'bg-blue-600 text-white shadow-lg' : 'bg-emerald-600 text-white shadow-lg'">
                            <span x-text="m.simbolo"></span>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-800 text-sm" x-text="m.nombre"></h2>
                            <span class="font-mono text-xs font-bold text-slate-400" x-text="m.codigo"></span>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full" :class="m.es_base == 1 ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'" x-text="m.es_base == 1 ? 'Moneda Base' : 'Extranjera'"></span>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex justify-between items-center">
                    <span class="text-xs text-slate-500 font-medium">Tasa de Conversión:</span>
                    <span class="text-sm font-mono font-black text-slate-800" x-text="m.es_base == 1 ? '1.000000 (Base)' : (m.tasa_actual ? parseFloat(m.tasa_actual).toFixed(4) + ' Bs.' : 'Sin tasa')"></span>
                </div>
                <div class="text-[10px] text-slate-400 font-mono text-right" x-show="m.fecha_tasa" x-text="'Última vigencia: ' + m.fecha_tasa"></div>
            </div>
        </template>
    </div>

    <!-- Histórico de Tasas -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 flex justify-between items-center">
            <span>Histórico de Tasas de Cambio</span>
            <span class="text-[11px] font-mono text-slate-400">Últimos 30 registros</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3">Fecha de Vigencia</th>
                        <th class="p-3">Moneda</th>
                        <th class="p-3 text-right">Tasa Registrada (VES)</th>
                        <th class="p-3 text-right">Fecha / Hora Registro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium font-mono">
                    <template x-for="h in historico" :key="h.id">
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-bold text-slate-800" x-text="h.fecha"></td>
                            <td class="p-3 text-blue-600" x-text="h.moneda_codigo + ' (' + h.moneda_nombre + ')'"></td>
                            <td class="p-3 text-right font-black text-emerald-600 text-sm" x-text="parseFloat(h.tasa).toFixed(4) + ' Bs.'"></td>
                            <td class="p-3 text-right text-slate-400 text-[11px]" x-text="h.created_at"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Actualizar Tasa -->
    <div x-show="modalTasa" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="modalTasa = false">
            <h3 class="font-bold text-base text-slate-800">Actualizar Tasa de Cambio</h3>
            <form @submit.prevent="guardarTasa()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Moneda *</label>
                    <select x-model="formTasa.moneda_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                        <template x-for="m in monedas.filter(x => x.es_base != 1)" :key="m.id">
                            <option :value="m.id" x-text="m.codigo + ' - ' + m.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tasa Oficial (Bs por 1 Unidad) *</label>
                    <input type="number" step="0.0001" x-model="formTasa.tasa" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-black text-emerald-600 text-sm">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Fecha de Vigencia *</label>
                    <input type="date" x-model="formTasa.fecha" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalTasa = false" class="px-4 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2.5 rounded-xl shadow">Fijar Tasa Oficial</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function maestroMonedasApp() {
    return {
        monedas: [],
        historico: [],
        modalTasa: false,
        formTasa: {
            moneda_id: 2,
            tasa: <?= \App\Core\Database::getTasaActualUsd() ?>,
            fecha: new Date().toISOString().split('T')[0]
        },
        async init() {
            await this.cargarMonedas();
        },
        async cargarMonedas() {
            try {
                const res = await fetch('/api/maestros/monedas');
                const json = await res.json();
                if (json.status === 'success') {
                    this.monedas = json.monedas || [];
                    this.historico = json.historico || [];
                    const usd = this.monedas.find(m => m.id == 2 || m.codigo == 'USD');
                    if (usd && usd.tasa_actual) {
                        this.formTasa.tasa = parseFloat(usd.tasa_actual);
                    }
                }
            } catch (e) {
                console.error(e);
            }
        },
        async guardarTasa() {
            try {
                const res = await fetch('/api/maestros/monedas/tasa', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formTasa)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalTasa = false;
                    await this.cargarMonedas();
                    alert(json.mensaje);
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
