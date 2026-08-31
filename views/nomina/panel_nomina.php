<?php
$pageTitle = 'Nómina y Gestión de Personal - mi ERP';
$activeMenu = 'nomina';
ob_start();
?>
<div class="space-y-4" x-data="nominaApp()" x-cloak>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs">Módulo RRHH</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Gestión de Nómina y Personal</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Cálculo de asignaciones, retenciones de ley (IVSS, FAOV, PIE), recibos PDF y asiento contable automático.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="calcularPrenomina()" :disabled="calculando" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-calculator" x-show="!calculando"></i>
                <i class="fa-solid fa-spinner fa-spin" x-show="calculando"></i> Calcular Prenómina
            </button>
            <button @click="abrirModalEmpleado(null)" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-user-plus"></i> Nuevo Empleado
            </button>
        </div>
    </div>

    <!-- KPIs de Nómina -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Total Empleados Activos</span>
            <p class="text-2xl font-black font-mono text-slate-900 mt-1" x-text="empleados.filter(e => e.estatus === 'ACTIVO').length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-indigo-500 uppercase">Masa Salarial Mensual ($)</span>
            <p class="text-xl font-black font-mono text-indigo-900 mt-1" x-text="'$' + fmt(empleados.reduce((a,e)=>a+Number(e.sueldo_base_mensual||0),0))"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-emerald-500 uppercase">Neto a Pagar Estimado ($)</span>
            <p class="text-xl font-black font-mono text-emerald-900 mt-1" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.neto_pagar||0),0))"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-red-400 uppercase">Total Retenciones ($)</span>
            <p class="text-xl font-black font-mono text-red-700 mt-1" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.total_deducciones||0),0))"></p>
        </div>
    </div>

    <!-- Pestañas -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="border-b border-slate-200 px-5 flex gap-0">
            <button @click="tab='empleados'" class="py-3 px-4 text-xs font-bold border-b-2 transition" :class="tab==='empleados' ? 'border-indigo-600 text-indigo-700 bg-indigo-50/40' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-solid fa-users mr-1.5"></i>Nómina de Empleados
            </button>
            <button @click="tab='prenomina'" class="py-3 px-4 text-xs font-bold border-b-2 transition" :class="tab==='prenomina' ? 'border-indigo-600 text-indigo-700 bg-indigo-50/40' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-solid fa-file-invoice-dollar mr-1.5"></i>Asistente de Prenómina
            </button>
        </div>

        <!-- TAB: EMPLEADOS -->
        <div x-show="tab === 'empleados'" class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3">Código</th>
                        <th class="p-3">Nombre Completo</th>
                        <th class="p-3">Cédula</th>
                        <th class="p-3">Cargo</th>
                        <th class="p-3">Departamento</th>
                        <th class="p-3 text-right">Sueldo Base ($)</th>
                        <th class="p-3 text-right">Bono Alim. ($)</th>
                        <th class="p-3 text-center">Estatus</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-for="e in empleados" :key="e.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 font-mono font-bold text-indigo-700" x-text="e.codigo"></td>
                            <td class="p-3 font-bold text-slate-800" x-text="e.nombres + ' ' + e.apellidos"></td>
                            <td class="p-3 font-mono text-slate-500" x-text="e.cedula || '—'"></td>
                            <td class="p-3 text-slate-600" x-text="e.cargo_nombre || 'General'"></td>
                            <td class="p-3 text-slate-500" x-text="e.departamento || '—'"></td>
                            <td class="p-3 text-right font-mono font-black text-slate-900" x-text="'$' + fmt(e.sueldo_base_mensual)"></td>
                            <td class="p-3 text-right font-mono text-emerald-700 font-bold" x-text="'$' + fmt(e.bono_alimentacion_mensual)"></td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="e.estatus === 'ACTIVO' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                      x-text="e.estatus || 'ACTIVO'"></span>
                            </td>
                            <td class="p-3 text-center">
                                <button @click="abrirModalEmpleado(e)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="empleados.length === 0">
                        <td colspan="9" class="py-14 text-center text-slate-400">
                            <i class="fa-solid fa-users text-3xl text-slate-200 block mb-2"></i>
                            No hay empleados registrados en el sistema.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TAB: ASISTENTE PRENÓMINA -->
        <div x-show="tab === 'prenomina'">
            <!-- Configuración del Período -->
            <div class="p-5 bg-slate-50 border-b border-slate-200 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Período Quincenal</label>
                    <select x-model="config.periodo_id" class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-white font-semibold">
                        <option value="1">1ª Quincena - <?= date('M Y') ?></option>
                        <option value="2">2ª Quincena - <?= date('M Y') ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Banco Pagador</label>
                    <select x-model="config.cuenta_bancaria_id" class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-white font-semibold">
                        <option value="">-- Seleccionar Banco --</option>
                        <template x-for="b in bancos" :key="b.id">
                            <option :value="b.id" x-text="b.nombre_banco + ' (Disp: $' + fmt(b.saldo_actual) + ')'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Tasa BCV (VES/USD)</label>
                    <div class="text-xs border border-slate-200 rounded-xl p-2.5 bg-white font-mono font-bold text-blue-700"><?= number_format(\App\Core\Database::getTasaActualUsd(), 2) ?> Bs/$</div>
                </div>
                <div class="flex items-end">
                    <button @click="procesarPagoDefinitivo()" :disabled="!config.cuenta_bancaria_id || procesando || prenomina.length === 0"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                        <i class="fa-solid fa-money-check-dollar" x-show="!procesando"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="procesando"></i>
                        <span x-text="procesando ? 'Procesando...' : 'Pagar y Contabilizar'"></span>
                    </button>
                </div>
            </div>

            <!-- Tabla de Prenómina con Deducciones de Ley -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="p-3" rowspan="2">Empleado</th>
                            <th class="p-3 text-right" rowspan="2">Sueldo Quincenal ($)</th>
                            <th class="p-3 text-right" rowspan="2">Bono Alim. ($)</th>
                            <th class="p-3 text-center border-l border-slate-300 bg-red-50 text-red-600" colspan="4">⬇ Deducciones de Ley</th>
                            <th class="p-3 text-right border-l border-slate-300 bg-emerald-50 text-emerald-700" rowspan="2">NETO ($)</th>
                            <th class="p-3 text-center border-l border-slate-300 bg-blue-50 text-blue-700" colspan="2">⬆ Aportes Patronales</th>
                        </tr>
                        <tr>
                            <th class="p-2.5 text-right bg-red-50 text-red-500 border-l border-slate-300">IVSS 4%</th>
                            <th class="p-2.5 text-right bg-red-50 text-red-500">FAOV 1%</th>
                            <th class="p-2.5 text-right bg-red-50 text-red-500">SPF 0.5%</th>
                            <th class="p-2.5 text-right bg-red-50 text-red-500">ISLR Est.</th>
                            <th class="p-2.5 text-right bg-blue-50 text-blue-600 border-l border-slate-300">IVSS Pat. 9%</th>
                            <th class="p-2.5 text-right bg-blue-50 text-blue-600">FAOV Pat. 2%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <template x-for="(r, i) in prenomina" :key="i">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-3 font-bold text-slate-800" x-text="r.nombres + ' ' + r.apellidos"></td>
                                <td class="p-3 text-right font-mono text-slate-700" x-text="'$' + fmt(r.sueldo_quincenal)"></td>
                                <td class="p-3 text-right font-mono text-emerald-700 font-bold" x-text="'$' + fmt(r.bono_quincenal)"></td>
                                <td class="p-3 text-right font-mono text-red-600 border-l border-slate-200" x-text="'$' + fmt(r.deduccion_ivss)"></td>
                                <td class="p-3 text-right font-mono text-red-600" x-text="'$' + fmt(r.deduccion_faov)"></td>
                                <td class="p-3 text-right font-mono text-red-600" x-text="'$' + fmt(r.deduccion_spf)"></td>
                                <td class="p-3 text-right font-mono text-red-600" x-text="'$' + fmt(r.deduccion_islr)"></td>
                                <td class="p-3 text-right font-mono font-black text-emerald-700 border-l border-slate-200" x-text="'$' + fmt(r.neto_pagar)"></td>
                                <td class="p-3 text-right font-mono text-blue-700 border-l border-slate-200" x-text="'$' + fmt(r.aporte_ivss_patron)"></td>
                                <td class="p-3 text-right font-mono text-blue-700" x-text="'$' + fmt(r.aporte_faov_patron)"></td>
                            </tr>
                        </template>
                        <tr x-show="prenomina.length === 0">
                            <td colspan="10" class="py-14 text-center text-slate-400">
                                <i class="fa-solid fa-calculator text-3xl text-slate-200 block mb-2"></i>
                                Haga clic en "Calcular Prenómina" para generar la liquidación del período.
                            </td>
                        </tr>
                    </tbody>
                    <!-- Totales -->
                    <tfoot class="bg-slate-100 border-t-2 border-slate-300" x-show="prenomina.length > 0">
                        <tr class="font-black text-xs">
                            <td class="p-3 text-right uppercase text-slate-600" colspan="2">Totales del Período:</td>
                            <td class="p-3 text-right font-mono text-emerald-800" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.bono_quincenal||0),0))"></td>
                            <td class="p-3 text-right font-mono text-red-700 border-l border-slate-300" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.deduccion_ivss||0),0))"></td>
                            <td class="p-3 text-right font-mono text-red-700" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.deduccion_faov||0),0))"></td>
                            <td class="p-3 text-right font-mono text-red-700" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.deduccion_spf||0),0))"></td>
                            <td class="p-3 text-right font-mono text-red-700" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.deduccion_islr||0),0))"></td>
                            <td class="p-3 text-right font-mono text-emerald-900 text-sm border-l border-slate-300" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.neto_pagar||0),0))"></td>
                            <td class="p-3 text-right font-mono text-blue-900 border-l border-slate-300" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.aporte_ivss_patron||0),0))"></td>
                            <td class="p-3 text-right font-mono text-blue-900" x-text="'$' + fmt(prenomina.reduce((a,r)=>a+Number(r.aporte_faov_patron||0),0))"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL: FICHA DE EMPLEADO -->
    <div x-show="modalEmpleado" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[95vh]" @click.away="modalEmpleado = false">
            <div class="bg-gradient-to-r from-slate-900 to-indigo-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-user-tie text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold" x-text="formEmp.id ? 'Editar Empleado: ' + formEmp.nombres : 'Nuevo Empleado'"></h2>
                        <p class="text-[10px] text-slate-400">Ficha Personal y Datos de Nómina</p>
                    </div>
                </div>
                <button @click="modalEmpleado = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form @submit.prevent="guardarEmpleado()" class="p-6 overflow-y-auto flex-1 text-xs space-y-5">
                <!-- Datos Personales -->
                <div>
                    <h4 class="font-bold text-slate-600 mb-3 text-xs uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-id-card text-indigo-600"></i> Datos Personales e Identificación
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Código Empleado</label>
                            <input type="text" x-model="formEmp.codigo" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-indigo-700 focus:outline-none focus:border-indigo-500" placeholder="EMP-001">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nombres *</label>
                            <input type="text" x-model="formEmp.nombres" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Apellidos *</label>
                            <input type="text" x-model="formEmp.apellidos" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Cédula de Identidad</label>
                            <input type="text" x-model="formEmp.cedula" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="V-12345678">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Teléfono</label>
                            <input type="text" x-model="formEmp.telefono" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="+58 4XX XXX XXXX">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Fecha de Ingreso</label>
                            <input type="date" x-model="formEmp.fecha_ingreso" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Datos Laborales -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <h4 class="font-bold text-slate-600 mb-3 text-xs uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-briefcase text-blue-600"></i> Datos Laborales y Nómina
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Cargo / Posición</label>
                            <input type="text" x-model="formEmp.cargo_nombre" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500" placeholder="Ej: Vendedor, Contador">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Departamento</label>
                            <input type="text" x-model="formEmp.departamento" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500" placeholder="Ej: Ventas, Administración">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tipo de Jornada</label>
                            <select x-model="formEmp.tipo_jornada" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="COMPLETO">Tiempo Completo</option>
                                <option value="MEDIO">Medio Tiempo</option>
                                <option value="DESTAJO">Por Destajo</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Sueldo Base Mensual (USD) *</label>
                            <input type="number" step="0.01" x-model.number="formEmp.sueldo_base_mensual" required @input="calcularDeducciones()" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-indigo-700 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Bono de Alimentación Mensual ($)</label>
                            <input type="number" step="0.01" x-model.number="formEmp.bono_alimentacion_mensual" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-mono text-emerald-700 focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Número Cuenta / Banco</label>
                            <input type="text" x-model="formEmp.cuenta_bancaria" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="IBAN o Nº cuenta">
                        </div>
                    </div>
                </div>

                <!-- Preview de Deducciones -->
                <div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-4 rounded-2xl border border-indigo-100" x-show="formEmp.sueldo_base_mensual > 0">
                    <h4 class="font-bold text-indigo-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-indigo-600"></i> Pre-visualización de Recibo de Nómina Quincenal
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white p-2.5 rounded-xl border border-indigo-100 text-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase block">Sueldo Quincenal</span>
                            <span class="font-mono font-black text-slate-900 text-sm" x-text="'$' + fmt((formEmp.sueldo_base_mensual || 0) / 2)"></span>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-red-100 text-center">
                            <span class="text-[10px] font-bold text-red-500 uppercase block">Deducciones Obreras</span>
                            <span class="font-mono font-black text-red-700 text-sm" x-text="'$' + fmt(totalDeduc(formEmp.sueldo_base_mensual))"></span>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-emerald-100 text-center">
                            <span class="text-[10px] font-bold text-emerald-600 uppercase block">Neto a Recibir</span>
                            <span class="font-mono font-black text-emerald-900 text-sm" x-text="'$' + fmt(((formEmp.sueldo_base_mensual||0)/2) - totalDeduc(formEmp.sueldo_base_mensual) + ((formEmp.bono_alimentacion_mensual||0)/2))"></span>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-blue-100 text-center">
                            <span class="text-[10px] font-bold text-blue-600 uppercase block">Carga Patronal Estimada</span>
                            <span class="font-mono font-black text-blue-900 text-sm" x-text="'$' + fmt(((formEmp.sueldo_base_mensual||0)/2) * 0.11)"></span>
                        </div>
                    </div>
                    <!-- Desglose de deducciones -->
                    <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2">
                        <div class="text-[11px] text-center">
                            <span class="text-red-500 font-bold block">IVSS Obrero (4%)</span>
                            <span class="font-mono font-bold" x-text="'$' + fmt(((formEmp.sueldo_base_mensual||0)/2) * 0.04)"></span>
                        </div>
                        <div class="text-[11px] text-center">
                            <span class="text-red-500 font-bold block">FAOV Obrero (1%)</span>
                            <span class="font-mono font-bold" x-text="'$' + fmt(((formEmp.sueldo_base_mensual||0)/2) * 0.01)"></span>
                        </div>
                        <div class="text-[11px] text-center">
                            <span class="text-red-500 font-bold block">SPF / RPOC (0.5%)</span>
                            <span class="font-mono font-bold" x-text="'$' + fmt(((formEmp.sueldo_base_mensual||0)/2) * 0.005)"></span>
                        </div>
                        <div class="text-[11px] text-center">
                            <span class="text-slate-500 font-bold block">ISLR Estimado</span>
                            <span class="font-mono font-bold text-slate-500">— (según UT)</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="modalEmpleado = false" class="px-5 py-2 text-slate-600 hover:text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition">Cancelar</button>
                    <button type="submit" :disabled="guardandoEmp" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-extrabold px-7 py-2.5 rounded-xl transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk" x-show="!guardandoEmp"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="guardandoEmp"></i>
                        <span x-text="guardandoEmp ? 'Guardando...' : 'Guardar Empleado'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function nominaApp() {
    return {
        tab: 'empleados',
        empleados: [],
        bancos: [],
        prenomina: [],
        calculando: false,
        procesando: false,
        modalEmpleado: false,
        guardandoEmp: false,
        config: { periodo_id: 1, cuenta_bancaria_id: '' },
        formEmp: {
            id: null, codigo: '', nombres: '', apellidos: '', cedula: '', telefono: '',
            fecha_ingreso: new Date().toISOString().split('T')[0],
            cargo_nombre: '', departamento: '', tipo_jornada: 'COMPLETO',
            sueldo_base_mensual: 0, bono_alimentacion_mensual: 0, cuenta_bancaria: '', estatus: 'ACTIVO'
        },

        init() {
            this.cargarDatos();
        },

        async cargarDatos() {
            try {
                const [rEmp, rBnc] = await Promise.all([
                    fetch('/api/nomina/empleados'),
                    fetch('/api/bancos/cuentas')
                ]);
                this.empleados = (await rEmp.json()).data || [];
                this.bancos = (await rBnc.json()).data || [];
            } catch(e) {}
        },

        async calcularPrenomina() {
            this.calculando = true;
            this.tab = 'prenomina';
            try {
                const res = await fetch(`/api/nomina/prenomina?periodo_id=${this.config.periodo_id}`);
                const json = await res.json();
                if (json.data?.empleados) {
                    this.prenomina = json.data.empleados;
                } else if (json.data?.resumen) {
                    // Fallback: calcular con datos locales
                    this.prenomina = this.empleados.map(e => this.calcularRenglon(e));
                } else {
                    this.prenomina = this.empleados.map(e => this.calcularRenglon(e));
                }
            } catch(e) {
                // Modo offline: calcular con datos locales
                this.prenomina = this.empleados.map(e => this.calcularRenglon(e));
            } finally {
                this.calculando = false;
            }
        },

        calcularRenglon(e) {
            const sueldo_quincenal = Number(e.sueldo_base_mensual || 0) / 2;
            const bono_quincenal = Number(e.bono_alimentacion_mensual || 0) / 2;
            const deduccion_ivss = sueldo_quincenal * 0.04;
            const deduccion_faov = sueldo_quincenal * 0.01;
            const deduccion_spf = sueldo_quincenal * 0.005;
            const deduccion_islr = 0;
            const total_deducciones = deduccion_ivss + deduccion_faov + deduccion_spf + deduccion_islr;
            const neto_pagar = sueldo_quincenal + bono_quincenal - total_deducciones;
            const aporte_ivss_patron = sueldo_quincenal * 0.09;
            const aporte_faov_patron = sueldo_quincenal * 0.02;
            return {
                ...e, sueldo_quincenal, bono_quincenal,
                deduccion_ivss, deduccion_faov, deduccion_spf, deduccion_islr,
                total_deducciones, neto_pagar, aporte_ivss_patron, aporte_faov_patron
            };
        },

        totalDeduc(sueldo_mensual) {
            const q = Number(sueldo_mensual || 0) / 2;
            return q * 0.04 + q * 0.01 + q * 0.005;
        },

        abrirModalEmpleado(emp) {
            if (emp) {
                this.formEmp = { ...emp };
            } else {
                this.formEmp = {
                    id: null,
                    codigo: 'EMP-' + String(this.empleados.length + 1).padStart(4, '0'),
                    nombres: '', apellidos: '', cedula: '', telefono: '',
                    fecha_ingreso: new Date().toISOString().split('T')[0],
                    cargo_nombre: '', departamento: '', tipo_jornada: 'COMPLETO',
                    sueldo_base_mensual: 0, bono_alimentacion_mensual: 0, cuenta_bancaria: '', estatus: 'ACTIVO'
                };
            }
            this.modalEmpleado = true;
        },

        async guardarEmpleado() {
            this.guardandoEmp = true;
            try {
                const url = this.formEmp.id ? `/api/nomina/empleados/${this.formEmp.id}` : '/api/nomina/empleados';
                const method = this.formEmp.id ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formEmp)
                });
                const json = await res.json();
                if (json.status === 'success' || json.id) {
                    this.modalEmpleado = false;
                    await this.cargarDatos();
                    alert('✅ Empleado guardado correctamente.');
                } else {
                    alert('❌ Error: ' + (json.message || 'No se pudo guardar'));
                }
            } catch(e) {
                alert('Error: ' + e.message);
            } finally {
                this.guardandoEmp = false;
            }
        },

        async procesarPagoDefinitivo() {
            if (!this.config.cuenta_bancaria_id) { alert('Seleccione una cuenta bancaria pagadora.'); return; }
            if (!confirm('¿Confirma cerrar el período y emitir el asiento contable de nómina?')) return;
            this.procesando = true;
            try {
                const res = await fetch('/api/nomina/cierre', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ periodo_id: this.config.periodo_id, cuenta_bancaria_id: this.config.cuenta_bancaria_id, usuario_id: 1 })
                });
                const json = await res.json();
                alert(json.status === 'success' ? ('✅ ' + json.message) : ('❌ ' + json.message));
            } catch(e) {
                alert('❌ Error: ' + e.message);
            } finally {
                this.procesando = false;
            }
        },

        calcularDeducciones() {},

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