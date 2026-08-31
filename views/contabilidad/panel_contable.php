<?php
$pageTitle = 'Contabilidad & Libro Diario - mi ERP';
$activeMenu = 'contabilidad';
ob_start();
?>
<div class="space-y-4" x-data="contabilidadApp()" x-cloak>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs">Módulo Contable</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Libro Diario & Contabilidad Integrada</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Asientos automáticos por doble partida, Balance de Comprobación y Estado de Resultados.</p>
        </div>
        <div class="flex items-center gap-2">
            <input type="date" x-model="filtros.desde" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-mono">
            <span class="text-slate-400 text-xs">—</span>
            <input type="date" x-model="filtros.hasta" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-mono">
            <button @click="cargarDatos()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass"></i> Consultar
            </button>
            <button @click="abrirNuevoAsiento()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i> Nuevo Asiento
            </button>
        </div>
    </div>

    <!-- Pestañas -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="border-b border-slate-200 px-5 flex gap-0">
            <button @click="tab='comprobantes'" class="py-3 px-4 text-xs font-bold border-b-2 transition" :class="tab==='comprobantes' ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-solid fa-scroll mr-1.5"></i>Comprobantes de Diario
            </button>
            <button @click="tab='balance'" class="py-3 px-4 text-xs font-bold border-b-2 transition" :class="tab==='balance' ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-solid fa-scale-balanced mr-1.5"></i>Balance de Comprobación
            </button>
            <button @click="tab='puc'" class="py-3 px-4 text-xs font-bold border-b-2 transition" :class="tab==='puc' ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-solid fa-sitemap mr-1.5"></i>Plan Único de Cuentas (PUC)
            </button>
        </div>

        <!-- TAB: COMPROBANTES DE DIARIO -->
        <div x-show="tab === 'comprobantes'" class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3">Nº Comprobante</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Concepto</th>
                        <th class="p-3">Referencia</th>
                        <th class="p-3 text-right">Total Debe ($)</th>
                        <th class="p-3 text-right">Total Haber ($)</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Detalles</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-for="c in comprobantes" :key="c.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 font-mono font-bold text-blue-700" x-text="c.numero_comprobante"></td>
                            <td class="p-3 font-mono text-slate-600" x-text="c.fecha_asiento || c.fecha"></td>
                            <td class="p-3 text-slate-800" x-text="c.concepto"></td>
                            <td class="p-3 text-slate-500 font-mono text-[10px]" x-text="c.documento_referencia || '—'"></td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900" x-text="'$' + fmt(c.total_debe)"></td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900" x-text="'$' + fmt(c.total_haber)"></td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="c.estado === 'ASENTADO' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                      x-text="c.estado || 'ASENTADO'"></span>
                            </td>
                            <td class="p-3 text-center">
                                <button @click="verDetalleAsiento(c)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Ver detalle">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="comprobantes.length === 0 && !cargando">
                        <td colspan="8" class="text-center py-14 text-slate-400">
                            <i class="fa-solid fa-scroll text-3xl text-slate-200 block mb-2"></i>
                            No hay comprobantes contables en el período seleccionado.
                        </td>
                    </tr>
                    <tr x-show="cargando">
                        <td colspan="8" class="text-center py-14 text-slate-400">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Cargando asientos...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TAB: BALANCE DE COMPROBACIÓN -->
        <div x-show="tab === 'balance'" class="overflow-x-auto">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-600">Período: <span class="font-mono text-blue-700" x-text="filtros.desde + ' al ' + filtros.hasta"></span></span>
                <div class="flex gap-3">
                    <span class="text-xs font-bold text-slate-500">Σ Debe: <span class="font-mono font-black text-slate-900" x-text="'$' + fmt(balance.reduce((a,b)=>a+Number(b.total_debe||0),0))"></span></span>
                    <span class="text-xs font-bold text-slate-500">Σ Haber: <span class="font-mono font-black text-slate-900" x-text="'$' + fmt(balance.reduce((a,b)=>a+Number(b.total_haber||0),0))"></span></span>
                </div>
            </div>
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3 w-24">Código</th>
                        <th class="p-3">Cuenta Contable</th>
                        <th class="p-3 text-center w-16">Nivel</th>
                        <th class="p-3 text-right">Debe ($)</th>
                        <th class="p-3 text-right">Haber ($)</th>
                        <th class="p-3 text-right">Saldo Deudor</th>
                        <th class="p-3 text-right">Saldo Acreedor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="b in balance" :key="b.cuenta_id">
                        <tr :class="b.nivel <= 2 ? 'bg-slate-50 font-bold' : 'hover:bg-slate-50 transition font-medium'">
                            <td class="p-3 font-mono text-blue-700 font-bold" x-text="b.codigo"></td>
                            <td class="p-3 font-sans text-slate-800" :style="'padding-left: ' + ((b.nivel - 1) * 16 + 12) + 'px'" x-text="b.descripcion"></td>
                            <td class="p-3 text-center">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                      :class="b.nivel === 1 ? 'bg-blue-100 text-blue-700' : b.nivel === 2 ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-500'"
                                      x-text="'N' + b.nivel"></span>
                            </td>
                            <td class="p-3 text-right font-mono" x-text="'$' + fmt(b.total_debe)"></td>
                            <td class="p-3 text-right font-mono" x-text="'$' + fmt(b.total_haber)"></td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900" x-text="Number(b.saldo_deudor||0) > 0 ? '$' + fmt(b.saldo_deudor) : '—'"></td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900" x-text="Number(b.saldo_acreedor||0) > 0 ? '$' + fmt(b.saldo_acreedor) : '—'"></td>
                        </tr>
                    </template>
                    <tr x-show="balance.length === 0">
                        <td colspan="7" class="text-center py-14 text-slate-400">
                            No hay movimientos contables en el período.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TAB: PLAN ÚNICO DE CUENTAS -->
        <div x-show="tab === 'puc'" class="overflow-x-auto">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center gap-3">
                <div class="relative flex-1 max-w-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" x-model="filtroPuc" placeholder="Buscar cuenta..." class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-blue-500">
                </div>
                <span class="text-xs text-slate-500 font-bold" x-text="planCuentasFiltrado.length + ' cuentas'"></span>
            </div>
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3">Código</th>
                        <th class="p-3">Descripción de la Cuenta</th>
                        <th class="p-3 text-center">Nivel</th>
                        <th class="p-3 text-center">Tipo Saldo</th>
                        <th class="p-3 text-center">Acepta Movim.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="c in planCuentasFiltrado" :key="c.id">
                        <tr :class="c.nivel <= 2 ? 'bg-slate-50/80 font-bold' : 'font-medium hover:bg-slate-50 transition'">
                            <td class="p-3 font-mono font-bold text-blue-700" x-text="c.codigo"></td>
                            <td class="p-3 font-sans text-slate-800" :style="'padding-left: ' + ((c.nivel - 1) * 16 + 12) + 'px'" x-text="c.descripcion"></td>
                            <td class="p-3 text-center">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                      :class="c.nivel === 1 ? 'bg-blue-100 text-blue-700' : c.nivel === 2 ? 'bg-indigo-50 text-indigo-700' : c.nivel === 3 ? 'bg-slate-100 text-slate-600' : 'bg-slate-50 text-slate-400'"
                                      x-text="'N' + c.nivel"></span>
                            </td>
                            <td class="p-3 text-center font-bold text-[10px]" x-text="c.tipo_saldo || c.tipo || '—'"></td>
                            <td class="p-3 text-center">
                                <span class="w-5 h-5 inline-flex items-center justify-center rounded-full"
                                      :class="c.acepta_movimientos == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                                    <i :class="c.acepta_movimientos == 1 ? 'fa-solid fa-check text-[9px]' : 'fa-solid fa-xmark text-[9px]'"></i>
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: DETALLE DE COMPROBANTE -->
    <div x-show="modalDetalle" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[90vh]" @click.away="modalDetalle = false">
            <div class="bg-slate-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-scroll text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold" x-text="'Comprobante: ' + (asientoSeleccionado?.numero_comprobante || '')"></h2>
                        <p class="text-[10px] text-slate-400" x-text="asientoSeleccionado?.concepto || ''"></p>
                    </div>
                </div>
                <button @click="modalDetalle = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6 overflow-y-auto text-xs space-y-4">
                <div class="grid grid-cols-3 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Fecha</span>
                        <span class="font-mono font-bold text-slate-800" x-text="asientoSeleccionado?.fecha_asiento || asientoSeleccionado?.fecha"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Referencia</span>
                        <span class="font-mono text-slate-600" x-text="asientoSeleccionado?.documento_referencia || '—'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Registrado por</span>
                        <span class="font-sans text-slate-600" x-text="asientoSeleccionado?.usuario_nombre || 'Sistema'"></span>
                    </div>
                </div>

                <table class="w-full text-left border-collapse border border-slate-200 rounded-xl overflow-hidden">
                    <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="p-2.5">Cuenta Contable</th>
                            <th class="p-2.5">Descripción / Glosa</th>
                            <th class="p-2.5 text-right text-emerald-700">Debe ($)</th>
                            <th class="p-2.5 text-right text-red-600">Haber ($)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(r, i) in detalleAsiento" :key="i">
                            <tr class="hover:bg-slate-50">
                                <td class="p-2.5 font-mono font-bold text-blue-700" x-text="r.codigo_cuenta || r.cuenta_codigo || r.cuenta_id"></td>
                                <td class="p-2.5 font-sans text-slate-700" x-text="r.glosa || r.descripcion || r.nombre_cuenta || '—'"></td>
                                <td class="p-2.5 text-right font-mono font-bold text-emerald-700" x-text="Number(r.debe||0) > 0 ? '$' + fmt(r.debe) : '—'"></td>
                                <td class="p-2.5 text-right font-mono font-bold text-red-600" x-text="Number(r.haber||0) > 0 ? '$' + fmt(r.haber) : '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-100 border-t-2 border-slate-300 font-black">
                        <tr>
                            <td colspan="2" class="p-2.5 text-right text-xs font-bold text-slate-600 uppercase">Totales:</td>
                            <td class="p-2.5 text-right font-mono text-emerald-700" x-text="'$' + fmt(detalleAsiento.reduce((a,r)=>a+Number(r.debe||0),0))"></td>
                            <td class="p-2.5 text-right font-mono text-red-600" x-text="'$' + fmt(detalleAsiento.reduce((a,r)=>a+Number(r.haber||0),0))"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL: NUEVO ASIENTO DE DIARIO MANUAL -->
    <div x-show="modalNuevoAsiento" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[95vh]" @click.away="modalNuevoAsiento = false">
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-pen-nib text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold">Editor de Asiento de Diario Manual</h2>
                        <p class="text-[10px] text-slate-400">Partida doble · Σ Debe = Σ Haber para poder asentar</p>
                    </div>
                </div>
                <button @click="modalNuevoAsiento = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="p-6 overflow-y-auto flex-1 text-xs space-y-5">
                <!-- Cabecera del Asiento -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Concepto del Comprobante *</label>
                        <input type="text" x-model="formAsiento.concepto" placeholder="Ej: Venta a crédito cliente XYZ, Pago proveedor ABC..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-sans text-slate-800 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Fecha del Asiento</label>
                        <input type="date" x-model="formAsiento.fecha" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Documento de Referencia</label>
                        <input type="text" x-model="formAsiento.documento_referencia" placeholder="Ej: FAC-0001, OC-001..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipo de Comprobante</label>
                        <select x-model="formAsiento.tipo" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                            <option value="DIARIO">Diario General</option>
                            <option value="APERTURA">Apertura</option>
                            <option value="CIERRE">Cierre</option>
                            <option value="AJUSTE">Ajuste</option>
                        </select>
                    </div>
                </div>

                <!-- Renglones Debe / Haber -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-bold text-slate-700 flex items-center gap-2">
                            <i class="fa-solid fa-table-list text-blue-600"></i> Renglones Contables
                        </h4>
                        <button type="button" @click="agregarRenglon()" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs px-3 py-1.5 rounded-lg border border-blue-200 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar Rengón
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-2xl overflow-hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="p-2.5 w-10">#</th>
                                    <th class="p-2.5">Código de Cuenta</th>
                                    <th class="p-2.5">Nombre / Glosa del Rengón</th>
                                    <th class="p-2.5 text-right text-emerald-700 w-36">Debe ($)</th>
                                    <th class="p-2.5 text-right text-red-600 w-36">Haber ($)</th>
                                    <th class="p-2.5 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(r, i) in formAsiento.renglones" :key="i">
                                    <tr :class="i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'">
                                        <td class="p-2 text-slate-400 font-mono" x-text="i + 1"></td>
                                        <td class="p-2">
                                            <input type="text" x-model="r.codigo_cuenta" list="cuentasList" placeholder="Ej: 1.1.01.001" class="w-full bg-white border border-slate-200 rounded-lg p-1.5 font-mono text-blue-700 font-bold focus:outline-none focus:border-blue-500">
                                            <datalist id="cuentasList">
                                                <template x-for="c in planCuentas.filter(c => c.acepta_movimientos == 1)" :key="c.id">
                                                    <option :value="c.codigo" :label="c.descripcion"></option>
                                                </template>
                                            </datalist>
                                        </td>
                                        <td class="p-2">
                                            <input type="text" x-model="r.glosa" placeholder="Descripción de la partida..." class="w-full bg-white border border-slate-200 rounded-lg p-1.5 font-sans focus:outline-none focus:border-blue-500">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" step="0.01" x-model.number="r.debe" @input="r.haber = 0" class="w-full bg-emerald-50 border border-emerald-200 rounded-lg p-1.5 text-right font-mono font-bold text-emerald-700 focus:outline-none focus:border-emerald-500">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" step="0.01" x-model.number="r.haber" @input="r.debe = 0" class="w-full bg-red-50 border border-red-200 rounded-lg p-1.5 text-right font-mono font-bold text-red-600 focus:outline-none focus:border-red-400">
                                        </td>
                                        <td class="p-2 text-center">
                                            <button type="button" @click="eliminarRenglon(i)" class="text-slate-400 hover:text-red-600 p-1">
                                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="formAsiento.renglones.length === 0">
                                    <td colspan="6" class="p-6 text-center text-slate-400">Haga clic en "Agregar Renglón" para comenzar.</td>
                                </tr>
                            </tbody>
                            <!-- Fila de Totales con Balanceo -->
                            <tfoot class="border-t-2 border-slate-300">
                                <tr>
                                    <td colspan="3" class="p-3 text-right text-xs font-bold text-slate-600 uppercase">Totales del Asiento:</td>
                                    <td class="p-3 text-right font-mono font-black text-emerald-700 text-sm" x-text="'$' + fmt(totalDebe)"></td>
                                    <td class="p-3 text-right font-mono font-black text-red-600 text-sm" x-text="'$' + fmt(totalHaber)"></td>
                                    <td class="p-3 text-center">
                                        <span class="w-6 h-6 inline-flex items-center justify-center rounded-full text-[10px] font-black"
                                              :class="asientoBalanceado ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'">
                                            <i :class="asientoBalanceado ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
                                        </span>
                                    </td>
                                </tr>
                                <tr x-show="!asientoBalanceado">
                                    <td colspan="6" class="px-3 pb-2">
                                        <div class="bg-red-50 border border-red-200 text-red-700 text-[11px] font-bold rounded-xl p-2 flex items-center gap-2">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            Diferencia: <span class="font-mono" x-text="'$' + fmt(Math.abs(totalDebe - totalHaber))"></span>
                                            — El asiento NO está balanceado. Debe = Haber para poder asentar.
                                        </div>
                                    </td>
                                </tr>
                                <tr x-show="asientoBalanceado && formAsiento.renglones.length >= 2">
                                    <td colspan="6" class="px-3 pb-2">
                                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold rounded-xl p-2 flex items-center gap-2">
                                            <i class="fa-solid fa-check-circle"></i>
                                            ¡Asiento balanceado! Debe = Haber = <span class="font-mono" x-text="'$' + fmt(totalDebe)"></span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Botones -->
                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="modalNuevoAsiento = false" class="px-5 py-2 text-slate-600 hover:text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition">Cancelar</button>
                    <button type="button" @click="guardarAsiento()" :disabled="!asientoBalanceado || formAsiento.renglones.length < 2 || guardandoAsiento"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-extrabold px-7 py-2.5 rounded-xl transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk" x-show="!guardandoAsiento"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="guardandoAsiento"></i>
                        <span x-text="guardandoAsiento ? 'Asentando...' : 'Asentar Comprobante'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function contabilidadApp() {
    return {
        tab: 'comprobantes',
        cargando: false,
        filtros: {
            desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            hasta: new Date().toISOString().split('T')[0]
        },
        comprobantes: [],
        balance: [],
        planCuentas: [],
        filtroPuc: '',
        modalDetalle: false,
        asientoSeleccionado: null,
        detalleAsiento: [],
        modalNuevoAsiento: false,
        guardandoAsiento: false,
        formAsiento: {
            tipo: 'DIARIO',
            concepto: '',
            documento_referencia: '',
            fecha: new Date().toISOString().split('T')[0],
            renglones: []
        },

        init() {
            this.cargarDatos();
            this.cargarPlanCuentas();
        },

        async cargarDatos() {
            this.cargando = true;
            try {
                const [rA, rB] = await Promise.all([
                    fetch(`/api/contabilidad/asientos`),
                    fetch(`/api/contabilidad/balance?desde=${this.filtros.desde}&hasta=${this.filtros.hasta}`)
                ]);
                this.comprobantes = (await rA.json()).data || [];
                this.balance = (await rB.json()).data || [];
            } catch(e) {
                console.error(e);
            } finally {
                this.cargando = false;
            }
        },

        async cargarPlanCuentas() {
            try {
                const r = await fetch('/api/contabilidad/plan-cuentas');
                this.planCuentas = (await r.json()).data || [];
            } catch(e) {}
        },

        get planCuentasFiltrado() {
            if (!this.filtroPuc) return this.planCuentas;
            const q = this.filtroPuc.toLowerCase();
            return this.planCuentas.filter(c => c.codigo.toLowerCase().includes(q) || c.descripcion.toLowerCase().includes(q));
        },

        async verDetalleAsiento(comp) {
            this.asientoSeleccionado = comp;
            this.detalleAsiento = [];
            this.modalDetalle = true;
            try {
                const r = await fetch(`/api/contabilidad/asientos/${comp.id}/detalles`);
                const json = await r.json();
                this.detalleAsiento = json.data || [];
            } catch(e) {
                this.detalleAsiento = [];
            }
        },

        abrirNuevoAsiento() {
            this.formAsiento = {
                tipo: 'DIARIO',
                concepto: '',
                documento_referencia: 'MAN-' + Date.now(),
                fecha: new Date().toISOString().split('T')[0],
                renglones: [
                    { codigo_cuenta: '', glosa: '', debe: 0, haber: 0 },
                    { codigo_cuenta: '', glosa: '', debe: 0, haber: 0 }
                ]
            };
            this.modalNuevoAsiento = true;
        },

        agregarRenglon() {
            this.formAsiento.renglones.push({ codigo_cuenta: '', glosa: '', debe: 0, haber: 0 });
        },

        eliminarRenglon(i) {
            this.formAsiento.renglones.splice(i, 1);
        },

        get totalDebe() {
            return this.formAsiento.renglones.reduce((a, r) => a + Number(r.debe || 0), 0);
        },

        get totalHaber() {
            return this.formAsiento.renglones.reduce((a, r) => a + Number(r.haber || 0), 0);
        },

        get asientoBalanceado() {
            return Math.abs(this.totalDebe - this.totalHaber) < 0.01 && this.totalDebe > 0;
        },

        async guardarAsiento() {
            if (!this.asientoBalanceado) return;
            this.guardandoAsiento = true;
            try {
                const payload = {
                    tipo: this.formAsiento.tipo,
                    concepto: this.formAsiento.concepto,
                    documento_referencia: this.formAsiento.documento_referencia,
                    fecha: this.formAsiento.fecha,
                    usuario_id: 1,
                    renglones: this.formAsiento.renglones.map(r => ({
                        cuenta_codigo: r.codigo_cuenta,
                        glosa: r.glosa,
                        debe: Number(r.debe || 0),
                        haber: Number(r.haber || 0)
                    }))
                };
                const res = await fetch('/api/contabilidad/asientos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalNuevoAsiento = false;
                    await this.cargarDatos();
                    alert('✅ Comprobante ' + (json.comprobante_id ? '#' + json.comprobante_id : '') + ' registrado correctamente.');
                } else {
                    alert('❌ Error: ' + json.message);
                }
            } catch(e) {
                alert('Error de conexión: ' + e.message);
            } finally {
                this.guardandoAsiento = false;
            }
        },

        fmt(val) {
            return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>