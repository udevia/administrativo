<?php
$pageTitle = 'Activos Fijos & Depreciaciones - mi ERP';
$activeMenu = 'activos';
ob_start();
?>
<div class="space-y-4" x-data="activosFijosApp()" x-cloak>

    <!-- Encabezado y Acciones -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 font-bold text-xs">Activos Fijos</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Control de Bienes de Uso y Depreciaciones</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Inventario patrimonial, tabla de amortización por línea recta y enlace automático a contabilidad.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="ejecutarDepreciacionMes()" :disabled="ejecutando"
                    class="bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-calculator" x-show="!ejecutando"></i>
                <i class="fa-solid fa-spinner fa-spin" x-show="ejecutando"></i>
                Cierre Depreciación Mensual
            </button>
            <button @click="abrirModalActivo(null)"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i> Nuevo Activo
            </button>
        </div>
    </div>

    <!-- Tarjetas KPI Patrimoniales -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Costo Histórico</span>
            <p class="text-xl font-black font-mono text-slate-900 mt-1" x-text="'$' + fmt(totales.costo)"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-red-100 shadow-sm">
            <span class="text-[10px] font-bold text-red-400 uppercase tracking-wide">Deprec. Acumulada</span>
            <p class="text-xl font-black font-mono text-red-600 mt-1" x-text="'$' + fmt(totales.deprec)"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-blue-100 shadow-sm">
            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wide">Valor Neto en Libros</span>
            <p class="text-xl font-black font-mono text-blue-900 mt-1" x-text="'$' + fmt(totales.neto)"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Bienes Registrados</span>
            <p class="text-xl font-black font-mono text-slate-800 mt-1" x-text="activos.length"></p>
        </div>
    </div>

    <!-- Filtro y Tabla Maestra -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 flex flex-col md:flex-row gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="filtro" placeholder="Buscar por descripción, placa, serial..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-blue-500">
            </div>
            <select x-model="filtroEstado" class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:border-blue-500">
                <option value="">Todos los estados</option>
                <option value="ACTIVO">Activo</option>
                <option value="DEPRECIADO">Totalmente Depreciado</option>
                <option value="DADO_DE_BAJA">Dado de Baja</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3">Placa / Código</th>
                        <th class="p-3">Descripción del Bien</th>
                        <th class="p-3">Serial / Marca</th>
                        <th class="p-3">Categoría</th>
                        <th class="p-3">Ubicación</th>
                        <th class="p-3 text-right">Costo ($)</th>
                        <th class="p-3 text-center">Avance</th>
                        <th class="p-3 text-right text-red-500">Dep. Acum. ($)</th>
                        <th class="p-3 text-right text-blue-700">Valor Libros ($)</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-for="a in activosFiltrados" :key="a.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 font-mono font-bold text-amber-700" x-text="a.codigo_placa_activo"></td>
                            <td class="p-3">
                                <div class="font-bold text-slate-800" x-text="a.descripcion"></div>
                                <div class="text-[10px] text-slate-400" x-text="'Adq.: ' + (a.fecha_adquisicion || '—')"></div>
                            </td>
                            <td class="p-3">
                                <div class="font-mono text-slate-600" x-text="a.numero_serial || '—'"></div>
                                <div class="text-[10px] text-slate-400" x-text="a.marca || ''"></div>
                            </td>
                            <td class="p-3 text-slate-500" x-text="a.categoria_nombre || 'General'"></td>
                            <td class="p-3 text-slate-500" x-text="a.departamento_nombre || 'Principal'"></td>
                            <td class="p-3 text-right font-mono font-bold" x-text="'$' + fmt(a.costo_adquisicion_usd)"></td>
                            <td class="p-3 text-center">
                                <!-- Barra de progreso de depreciación -->
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-slate-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full"
                                             :class="progreso(a) >= 100 ? 'bg-red-500' : progreso(a) > 75 ? 'bg-amber-500' : 'bg-emerald-500'"
                                             :style="'width: ' + Math.min(100, progreso(a)) + '%'"></div>
                                    </div>
                                    <span class="text-[10px] font-mono font-bold text-slate-500" x-text="a.meses_depreciados + '/' + a.vida_util_meses"></span>
                                </div>
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-red-600" x-text="'$' + fmt(a.depreciacion_acumulada_usd)"></td>
                            <td class="p-3 text-right font-mono font-black text-blue-900" x-text="'$' + fmt(a.valor_en_libros_usd)"></td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="a.estado === 'ACTIVO' ? 'bg-emerald-100 text-emerald-800' : a.estado === 'DEPRECIADO' ? 'bg-slate-100 text-slate-600' : 'bg-red-100 text-red-700'"
                                      x-text="a.estado"></span>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="verTablaDepreciacion(a)" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Ver tabla de amortización">
                                        <i class="fa-solid fa-table-cells"></i>
                                    </button>
                                    <button @click="abrirModalActivo(a)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Editar activo">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="activosFiltrados.length === 0">
                        <td colspan="11" class="py-14 text-center text-slate-400">
                            <i class="fa-solid fa-boxes-packing text-3xl text-slate-200 block mb-2"></i>
                            No hay activos fijos registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: FICHA DE ACTIVO FIJO -->
    <div x-show="modalActivo" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[95vh]" @click.away="modalActivo = false">
            <div class="bg-gradient-to-r from-slate-900 to-amber-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-boxes-packing text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold" x-text="formActivo.id ? 'Editar Activo: ' + formActivo.codigo_placa_activo : 'Nuevo Activo Fijo'"></h2>
                        <p class="text-[10px] text-slate-400">Ficha patrimonial · Depreciación Línea Recta</p>
                    </div>
                </div>
                <button @click="modalActivo = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form @submit.prevent="guardarActivo()" class="p-6 overflow-y-auto flex-1 text-xs space-y-5">
                <!-- Sección 1: Identificación -->
                <div>
                    <h4 class="font-bold text-slate-700 mb-3 flex items-center gap-2 text-xs uppercase tracking-wide">
                        <i class="fa-solid fa-tag text-amber-600"></i> Identificación del Bien
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Código / Placa *</label>
                            <input type="text" x-model="formActivo.codigo_placa_activo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-amber-700 focus:outline-none focus:border-amber-500" placeholder="Ej: AF-0001">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Descripción del Bien *</label>
                            <input type="text" x-model="formActivo.descripcion" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-800 focus:outline-none focus:border-blue-500" placeholder="Ej: Computadora Dell Optiplex 7090">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nº Serial / Placa Vehículo</label>
                            <input type="text" x-model="formActivo.numero_serial" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="S/N o Placa">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Marca / Fabricante</label>
                            <input type="text" x-model="formActivo.marca" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500" placeholder="Ej: Dell, Toyota">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Modelo</label>
                            <input type="text" x-model="formActivo.modelo" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500" placeholder="Ej: Optiplex 7090, Corolla 2022">
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Clasificación y Ubicación -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <h4 class="font-bold text-slate-700 mb-3 flex items-center gap-2 text-xs uppercase tracking-wide">
                        <i class="fa-solid fa-sitemap text-blue-600"></i> Clasificación y Ubicación
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Categoría de Activo</label>
                            <select x-model="formActivo.categoria_activo_id" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="1">Equipo de Computación</option>
                                <option value="2">Maquinaria y Equipo</option>
                                <option value="3">Mobiliario y Enseres</option>
                                <option value="4">Vehículos y Transporte</option>
                                <option value="5">Edificaciones</option>
                                <option value="6">Terrenos</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Departamento / Área</label>
                            <input type="text" x-model="formActivo.departamento_nombre" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500" placeholder="Ej: Administración, Ventas">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Responsable Asignado</label>
                            <input type="text" x-model="formActivo.responsable" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500" placeholder="Nombre del empleado">
                        </div>
                    </div>
                </div>

                <!-- Sección 3: Datos de Adquisición y Depreciación -->
                <div>
                    <h4 class="font-bold text-slate-700 mb-3 flex items-center gap-2 text-xs uppercase tracking-wide">
                        <i class="fa-solid fa-calculator text-red-600"></i> Adquisición y Parámetros de Depreciación
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Fecha de Adquisición *</label>
                            <input type="date" x-model="formActivo.fecha_adquisicion" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Costo de Adquisición (USD) *</label>
                            <input type="number" step="0.01" x-model.number="formActivo.costo_adquisicion_usd" @input="calcularDepreciacion()" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Valor Residual (USD)</label>
                            <input type="number" step="0.01" x-model.number="formActivo.valor_residual_usd" @input="calcularDepreciacion()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Vida Útil (meses) *</label>
                            <select x-model.number="formActivo.vida_util_meses" @change="calcularDepreciacion()" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="24">2 años (24 meses) - Software / Hardware</option>
                                <option value="36">3 años (36 meses) - Equipo Electrónico</option>
                                <option value="60">5 años (60 meses) - Maquinaria / Mobiliario</option>
                                <option value="120">10 años (120 meses) - Vehículos / Equipos Pesados</option>
                                <option value="240">20 años (240 meses) - Edificaciones</option>
                                <option value="0">No depreciable (Terrenos)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Método de Depreciación</label>
                            <select x-model="formActivo.metodo_depreciacion" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="LINEA_RECTA">Línea Recta (Constante)</option>
                                <option value="UNIDADES_PRODUCCION">Unidades de Producción</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Proveedor / Factura de Compra</label>
                            <input type="text" x-model="formActivo.factura_compra" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="Ej: FAC-0001">
                        </div>
                    </div>

                    <!-- Preview de Depreciación -->
                    <div class="mt-4 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 p-4 rounded-2xl" x-show="formActivo.costo_adquisicion_usd > 0 && formActivo.vida_util_meses > 0">
                        <h5 class="font-bold text-amber-900 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-chart-line"></i> Pre-visualización del Plan de Depreciación (Línea Recta)
                        </h5>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-white p-2.5 rounded-xl border border-amber-200 text-center">
                                <span class="text-[10px] font-bold text-amber-600 uppercase block">Depreciación Mensual</span>
                                <span class="font-mono font-black text-amber-900 text-base" x-text="'$' + fmt(cuotaMensual)"></span>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-amber-200 text-center">
                                <span class="text-[10px] font-bold text-amber-600 uppercase block">Depreciación Anual</span>
                                <span class="font-mono font-black text-amber-900 text-base" x-text="'$' + fmt(cuotaMensual * 12)"></span>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-amber-200 text-center">
                                <span class="text-[10px] font-bold text-amber-600 uppercase block">Valor Residual</span>
                                <span class="font-mono font-black text-slate-800 text-base" x-text="'$' + fmt(formActivo.valor_residual_usd || 0)"></span>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-amber-200 text-center">
                                <span class="text-[10px] font-bold text-amber-600 uppercase block">Total a Depreciar</span>
                                <span class="font-mono font-black text-red-700 text-base" x-text="'$' + fmt((formActivo.costo_adquisicion_usd || 0) - (formActivo.valor_residual_usd || 0))"></span>
                            </div>
                        </div>
                        <!-- Mini tabla de primeros 5 meses -->
                        <div class="mt-3 overflow-hidden rounded-xl border border-amber-200">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-amber-100 text-amber-800 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="p-2">Período</th>
                                        <th class="p-2 text-right">Dep. Período ($)</th>
                                        <th class="p-2 text-right">Dep. Acum. ($)</th>
                                        <th class="p-2 text-right">Valor Libros ($)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-100">
                                    <template x-for="(m, i) in tablaDepreciacionPreview" :key="i">
                                        <tr class="bg-white">
                                            <td class="p-2 font-mono text-slate-600" x-text="'Mes ' + (i + 1)"></td>
                                            <td class="p-2 text-right font-mono font-bold text-red-600" x-text="'$' + fmt(m.cuota)"></td>
                                            <td class="p-2 text-right font-mono text-slate-700" x-text="'$' + fmt(m.acumulada)"></td>
                                            <td class="p-2 text-right font-mono font-bold text-blue-900" x-text="'$' + fmt(m.valor_libros)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="modalActivo = false" class="px-5 py-2 text-slate-600 hover:text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition">Cancelar</button>
                    <button type="submit" :disabled="guardandoActivo" class="bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-extrabold px-7 py-2.5 rounded-xl transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk" x-show="!guardandoActivo"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="guardandoActivo"></i>
                        <span x-text="guardandoActivo ? 'Guardando...' : 'Registrar Activo'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: TABLA COMPLETA DE AMORTIZACIÓN -->
    <div x-show="modalTablaDeprec" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[90vh]" @click.away="modalTablaDeprec = false">
            <div class="bg-slate-900 text-white p-5 flex justify-between items-center">
                <div>
                    <h2 class="font-bold" x-text="'Tabla de Amortización: ' + (activoDetalle?.codigo_placa_activo || '')"></h2>
                    <p class="text-[10px] text-slate-400" x-text="activoDetalle?.descripcion || ''"></p>
                </div>
                <button @click="modalTablaDeprec = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="overflow-y-auto flex-1">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px] sticky top-0">
                        <tr>
                            <th class="p-3">Período</th>
                            <th class="p-3 text-right">Dep. Mensual ($)</th>
                            <th class="p-3 text-right">Dep. Acumulada ($)</th>
                            <th class="p-3 text-right">Valor en Libros ($)</th>
                            <th class="p-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(fila, i) in tablaCompletaDeprec" :key="i">
                            <tr :class="i < (activoDetalle?.meses_depreciados || 0) ? 'bg-slate-50 text-slate-400' : 'bg-white font-medium'">
                                <td class="p-3 font-mono" x-text="'Mes ' + (i + 1)"></td>
                                <td class="p-3 text-right font-mono text-red-600 font-bold" x-text="'$' + fmt(fila.cuota)"></td>
                                <td class="p-3 text-right font-mono text-slate-700" x-text="'$' + fmt(fila.acumulada)"></td>
                                <td class="p-3 text-right font-mono font-black text-blue-900" x-text="'$' + fmt(fila.valor_libros)"></td>
                                <td class="p-3 text-center">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                          :class="i < (activoDetalle?.meses_depreciados || 0) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                          x-text="i < (activoDetalle?.meses_depreciados || 0) ? 'Aplicado' : 'Pendiente'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function activosFijosApp() {
    return {
        activos: [],
        filtro: '',
        filtroEstado: '',
        ejecutando: false,
        guardandoActivo: false,
        modalActivo: false,
        modalTablaDeprec: false,
        activoDetalle: null,
        cuotaMensual: 0,
        formActivo: {
            id: null, codigo_placa_activo: '', descripcion: '', numero_serial: '', marca: '', modelo: '',
            categoria_activo_id: 1, departamento_nombre: '', responsable: '', fecha_adquisicion: '',
            costo_adquisicion_usd: 0, valor_residual_usd: 0, vida_util_meses: 60,
            metodo_depreciacion: 'LINEA_RECTA', factura_compra: ''
        },

        init() { this.cargarDatos(); },

        async cargarDatos() {
            try {
                const r = await fetch('/api/activos-fijos');
                this.activos = (await r.json()).data || [];
            } catch(e) { this.activos = []; }
        },

        get activosFiltrados() {
            return this.activos.filter(a => {
                const q = this.filtro.toLowerCase();
                const matchQ = !q || a.descripcion?.toLowerCase().includes(q) || a.codigo_placa_activo?.toLowerCase().includes(q) || a.numero_serial?.toLowerCase().includes(q);
                const matchE = !this.filtroEstado || a.estado === this.filtroEstado;
                return matchQ && matchE;
            });
        },

        get totales() {
            return this.activos.reduce((acc, a) => ({
                costo: acc.costo + Number(a.costo_adquisicion_usd || 0),
                deprec: acc.deprec + Number(a.depreciacion_acumulada_usd || 0),
                neto: acc.neto + Number(a.valor_en_libros_usd || 0)
            }), { costo: 0, deprec: 0, neto: 0 });
        },

        progreso(a) {
            if (!a.vida_util_meses || a.vida_util_meses === 0) return 0;
            return Math.round((a.meses_depreciados / a.vida_util_meses) * 100);
        },

        calcularDepreciacion() {
            const base = Number(this.formActivo.costo_adquisicion_usd || 0) - Number(this.formActivo.valor_residual_usd || 0);
            const meses = Number(this.formActivo.vida_util_meses || 1);
            this.cuotaMensual = meses > 0 ? base / meses : 0;
        },

        get tablaDepreciacionPreview() {
            const cuota = this.cuotaMensual;
            const costo = Number(this.formActivo.costo_adquisicion_usd || 0);
            const residual = Number(this.formActivo.valor_residual_usd || 0);
            const preview = [];
            const mesesMax = Math.min(6, Number(this.formActivo.vida_util_meses || 1));
            let acum = 0;
            for (let i = 0; i < mesesMax; i++) {
                acum += cuota;
                preview.push({ cuota, acumulada: acum, valor_libros: Math.max(residual, costo - acum) });
            }
            return preview;
        },

        get tablaCompletaDeprec() {
            if (!this.activoDetalle) return [];
            const costo = Number(this.activoDetalle.costo_adquisicion_usd || 0);
            const residual = Number(this.activoDetalle.valor_residual_usd || 0);
            const meses = Number(this.activoDetalle.vida_util_meses || 1);
            const cuota = meses > 0 ? (costo - residual) / meses : 0;
            let acum = 0;
            return Array.from({ length: meses }, (_, i) => {
                acum += cuota;
                return { cuota, acumulada: acum, valor_libros: Math.max(residual, costo - acum) };
            });
        },

        abrirModalActivo(activo) {
            if (activo) {
                this.formActivo = { ...activo };
            } else {
                this.formActivo = {
                    id: null, codigo_placa_activo: 'AF-' + String(this.activos.length + 1).padStart(4, '0'),
                    descripcion: '', numero_serial: '', marca: '', modelo: '',
                    categoria_activo_id: 1, departamento_nombre: '', responsable: '',
                    fecha_adquisicion: new Date().toISOString().split('T')[0],
                    costo_adquisicion_usd: 0, valor_residual_usd: 0, vida_util_meses: 60,
                    metodo_depreciacion: 'LINEA_RECTA', factura_compra: ''
                };
            }
            this.calcularDepreciacion();
            this.modalActivo = true;
        },

        async guardarActivo() {
            this.guardandoActivo = true;
            try {
                const url = this.formActivo.id ? `/api/activos-fijos/${this.formActivo.id}` : '/api/activos-fijos';
                const method = this.formActivo.id ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formActivo)
                });
                const json = await res.json();
                if (json.status === 'success' || json.id) {
                    this.modalActivo = false;
                    await this.cargarDatos();
                    alert('✅ Activo registrado/actualizado correctamente.');
                } else {
                    alert('❌ Error: ' + json.message);
                }
            } catch(e) {
                alert('Error: ' + e.message);
            } finally {
                this.guardandoActivo = false;
            }
        },

        verTablaDepreciacion(activo) {
            this.activoDetalle = activo;
            this.modalTablaDeprec = true;
        },

        async ejecutarDepreciacionMes() {
            const fecha = prompt('Ingrese el período a depreciar (AAAA-MM):', new Date().toISOString().slice(0, 7));
            if (!fecha) return;
            const [ano, mes] = fecha.split('-').map(Number);
            this.ejecutando = true;
            try {
                const res = await fetch('/api/activos-fijos/depreciar-mensual', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ano, mes })
                });
                const json = await res.json();
                alert(json.mensaje || '✅ Depreciación ejecutada y asiento contable registrado.');
                await this.cargarDatos();
            } catch(e) {
                alert('❌ Error: ' + e.message);
            } finally {
                this.ejecutando = false;
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