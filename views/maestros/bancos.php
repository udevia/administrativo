<?php
$pageTitle = 'Cuentas Bancarias y Cajas de Tesorería - mi';
$activeMenu = 'maestros_bancos';
ob_start();
?>
<div class="space-y-4" x-data="maestroBancosApp()">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs">Tesorería & Cajas</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Cuentas Bancarias y Cajas</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Defina cuentas bancarias en bolívares y divisas, cajas chicas de efectivo y terminales POS.</p>
        </div>

        <button @click="nuevaCuenta()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-building-columns"></i>
            <span>Nueva Cuenta / Caja</span>
        </button>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                    <th class="p-3">Código</th>
                    <th class="p-3">Tipo</th>
                    <th class="p-3">Nombre Entidad / Caja</th>
                    <th class="p-3">Número de Cuenta</th>
                    <th class="p-3">Moneda</th>
                    <th class="p-3 text-right">Saldo Actual</th>
                    <th class="p-3 text-center">Estado</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
                <template x-for="b in bancos" :key="b.id">
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="p-3 font-mono font-bold text-blue-600" x-text="b.codigo"></td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="b.tipo === 'BANCO' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'" x-text="b.tipo"></span>
                        </td>
                        <td class="p-3 font-bold text-slate-800" x-text="b.nombre_banco"></td>
                        <td class="p-3 font-mono text-slate-500" x-text="b.numero_cuenta || 'N/A'"></td>
                        <td class="p-3 font-bold text-slate-700" x-text="b.moneda_codigo + ' (' + b.moneda_simbolo + ')'"></td>
                        <td class="p-3 text-right font-mono font-black text-slate-800 text-sm" x-text="b.moneda_simbolo + ' ' + parseFloat(b.saldo_actual || 0).toFixed(2)"></td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="b.estado == 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="b.estado == 1 ? 'Activa' : 'Inactiva'"></span>
                        </td>
                        <td class="p-3 text-center">
                            <button @click="editarCuenta(b)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Modal Formulario -->
    <div x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="modalAbierto = false">
            <h3 class="font-bold text-base text-slate-800" x-text="form.id ? 'Editar Cuenta' : 'Nueva Cuenta de Tesorería'"></h3>
            <form @submit.prevent="guardar()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tipo de Cuenta *</label>
                    <select x-model="form.tipo" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                        <option value="BANCO">Banco / Cuenta Corriente</option>
                        <option value="CAJA_EFECTIVO">Caja de Efectivo / Caja Chica</option>
                        <option value="CAJA_POS">Terminal Punto de Venta (POS)</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Código *</label>
                        <input type="text" x-model="form.codigo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-blue-600">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Moneda *</label>
                        <select x-model="form.moneda_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                            <option value="1">VES (Bolívares)</option>
                            <option value="2">USD (Dólares)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nombre Banco / Identificador *</label>
                    <input type="text" x-model="form.nombre_banco" required placeholder="Ej: Banesco Banco Universal o Caja Principal" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Número de Cuenta (20 dígitos)</label>
                    <input type="text" x-model="form.numero_cuenta" placeholder="0134-XXXX-XX-XXXXXXXXXX" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Saldo Inicial / Actual</label>
                    <input type="number" step="0.01" x-model="form.saldo_actual" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-slate-800">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalAbierto = false" class="px-4 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl shadow">Guardar Cuenta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function maestroBancosApp() {
    return {
        bancos: [],
        modalAbierto: false,
        form: { id: null, tipo: 'BANCO', codigo: '', nombre_banco: '', numero_cuenta: '', moneda_id: 1, saldo_actual: 0, estado: 1 },
        async init() {
            await this.cargarBancos();
        },
        async cargarBancos() {
            try {
                const res = await fetch('/api/maestros/bancos');
                const json = await res.json();
                if (json.status === 'success') this.bancos = json.data || [];
            } catch (e) {
                console.error(e);
            }
        },
        nuevaCuenta() {
            this.form = { id: null, tipo: 'BANCO', codigo: 'CTA-' + Math.floor(10 + Math.random() * 90), nombre_banco: '', numero_cuenta: '', moneda_id: 1, saldo_actual: 0, estado: 1 };
            this.modalAbierto = true;
        },
        editarCuenta(b) {
            this.form = JSON.parse(JSON.stringify(b));
            this.modalAbierto = true;
        },
        async guardar() {
            try {
                const res = await fetch('/api/maestros/bancos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalAbierto = false;
                    await this.cargarBancos();
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
