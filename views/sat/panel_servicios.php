<?php
$pageTitle = 'SAT - Taller y Servicios - mi ERP';
$activeMenu = 'sat';
ob_start();
?>
<div class="space-y-4" x-data="serviciosSatApp()" x-cloak>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs">Módulo SAT</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Servicios, Recepción y Taller</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Órdenes de trabajo con campos dinámicos por tipo de equipo, control Kanban de estados, repuestos del Kardex y facturación integrada.</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- KPIs rápidos -->
            <template x-for="est in estados.slice(0,4)" :key="est.id">
                <div class="text-center px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50">
                    <div class="text-[10px] font-bold text-slate-400 uppercase" x-text="est.nombre"></div>
                    <div class="text-lg font-black font-mono text-slate-800" x-text="filtrarPorEstado(est.id).length"></div>
                </div>
            </template>
            <button @click="abrirNueva()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-circle-plus"></i> Nueva Orden
            </button>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="overflow-x-auto pb-2">
        <div class="flex gap-3 min-w-max">
            <template x-for="est in estados" :key="est.id">
                <div class="w-52 bg-slate-100 rounded-2xl p-3 border border-slate-200 flex flex-col space-y-2 min-h-[600px]">
                    <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full" :class="est.color"></div>
                            <span class="text-xs font-extrabold text-slate-800 uppercase tracking-tight" x-text="est.nombre"></span>
                        </div>
                        <span class="text-[10px] font-mono font-bold bg-white text-slate-600 px-2 py-0.5 rounded-full border border-slate-200" x-text="filtrarPorEstado(est.id).length"></span>
                    </div>

                    <div class="space-y-2 flex-1 overflow-y-auto">
                        <template x-for="ot in filtrarPorEstado(est.id)" :key="ot.id">
                            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition space-y-2 cursor-pointer"
                                 @click="abrirDetalle(ot)">
                                <div class="flex justify-between items-start">
                                    <span class="font-mono font-black text-[11px] text-blue-700" x-text="ot.numero_orden"></span>
                                    <span class="text-[9px] text-slate-400 font-mono" x-text="formatFecha(ot.fecha_recepcion)"></span>
                                </div>
                                <p class="text-xs font-bold text-slate-900 leading-tight" x-text="ot.cliente_nombre || ot.razon_social"></p>
                                <p class="text-[10px] text-slate-500 leading-tight line-clamp-2" x-text="ot.falla_reportada_cliente"></p>

                                <!-- Tags tipo servicio -->
                                <div class="flex gap-1 flex-wrap">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700" x-text="tipoLabel(ot.tipo_servicio_id)"></span>
                                    <span x-show="ot.tiene_repuestos" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">Repuestos</span>
                                </div>

                                <!-- Avanzar estado -->
                                <div class="flex gap-1 pt-1 border-t border-slate-100" @click.stop>
                                    <template x-if="est.siguiente_id">
                                        <button @click.stop="avanzarEstado(ot, est.siguiente_id)"
                                                class="flex-1 text-[10px] font-bold py-1 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition flex items-center justify-center gap-1">
                                            <i class="fa-solid fa-arrow-right"></i>
                                            <span x-text="estadoNombre(est.siguiente_id)"></span>
                                        </button>
                                    </template>
                                    <button @click.stop="abrirLiquidar(ot)"
                                            class="px-2 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition" title="Liquidar / Facturar">
                                        <i class="fa-solid fa-receipt"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div x-show="filtrarPorEstado(est.id).length === 0" class="text-center py-8 text-slate-400 text-xs">
                            <i class="fa-regular fa-rectangle-list text-2xl block mb-1 text-slate-300"></i>
                            Sin órdenes
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ===== MODAL: NUEVA ORDEN DE RECEPCIÓN ===== -->
    <div x-show="modalNueva" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-slate-100">
            <div class="bg-gradient-to-r from-slate-900 to-blue-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div>
                        <h3 class="font-bold text-sm">Nueva Orden de Recepción SAT</h3>
                        <p class="text-[10px] text-slate-400">Complete la ficha técnica del equipo a reparar</p>
                    </div>
                </div>
                <button @click="modalNueva = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form @submit.prevent="guardarOrden()" class="p-6 overflow-y-auto space-y-5 flex-1 text-xs">

                <!-- Datos del cliente y equipo -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Cliente *</label>
                        <select x-model.number="form.cliente_id" required class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-semibold focus:outline-none focus:border-blue-500">
                            <option value="">-- Seleccione Cliente --</option>
                            <template x-for="c in clientes" :key="c.id">
                                <option :value="c.id" x-text="c.razon_social + ' (' + (c.documento_fiscal||'') + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipo de Servicio / Categoría *</label>
                        <select x-model.number="form.tipo_servicio_id" @change="form.campos_dinamicos = {}" required class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-semibold focus:outline-none focus:border-blue-500">
                            <template x-for="ts in tiposServicio" :key="ts.id">
                                <option :value="ts.id" x-text="ts.nombre"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Marca / Modelo del Equipo</label>
                        <input type="text" x-model="form.marca_modelo" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-mono focus:outline-none focus:border-blue-500" placeholder="Ej: Samsung Galaxy A54 / Dell XPS 15">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Serial / IMEI del Equipo</label>
                        <input type="text" x-model="form.serial_equipo" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-mono focus:outline-none focus:border-blue-500" placeholder="Número de serial o IMEI">
                    </div>
                </div>

                <!-- Campos Dinámicos por Tipo de Servicio -->
                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 space-y-3">
                    <h4 class="font-bold text-blue-900 flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-blue-600"></i>
                        Ficha Técnica: <span x-text="tipoLabel(form.tipo_servicio_id)" class="text-blue-700"></span>
                    </h4>

                    <!-- Computación / Celulares -->
                    <div x-show="form.tipo_servicio_id == 1" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Memoria RAM / Almacenamiento</label>
                            <input type="text" x-model="form.campos_dinamicos.ram" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-mono focus:outline-none focus:border-blue-500" placeholder="Ej: 8GB / 256GB SSD">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Cargador Incluido</label>
                            <select x-model="form.campos_dinamicos.cargador" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-bold">
                                <option value="">No especificado</option>
                                <option value="SI">Sí, incluye cargador</option>
                                <option value="NO">No incluye cargador</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Contraseña / PIN de Acceso</label>
                            <input type="text" x-model="form.campos_dinamicos.clave" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-mono" placeholder="PIN / Patrón / Sin clave">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Sistema Operativo</label>
                            <input type="text" x-model="form.campos_dinamicos.sistema_operativo" class="w-full bg-white border border-blue-200 rounded-xl p-2" placeholder="Ej: Windows 11, Android 14">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estado Físico Externo</label>
                            <select x-model="form.campos_dinamicos.estado_fisico" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-bold">
                                <option value="BUENO">Bueno (sin daños visibles)</option>
                                <option value="REGULAR">Regular (rayones leves)</option>
                                <option value="MALO">Malo (pantalla rota, golpes)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nivel de Batería al Ingreso</label>
                            <input type="text" x-model="form.campos_dinamicos.nivel_bateria" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-mono" placeholder="Ej: 45%">
                        </div>
                    </div>

                    <!-- Vehículo / Automotriz -->
                    <div x-show="form.tipo_servicio_id == 2" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Placa / Serial de Carrocería</label>
                            <input type="text" x-model="form.campos_dinamicos.placa" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-mono font-black uppercase focus:outline-none focus:border-blue-500" placeholder="ABC-123">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kilometraje Ingreso</label>
                            <input type="number" x-model.number="form.campos_dinamicos.kilometraje" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-mono" placeholder="145000">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nivel de Combustible</label>
                            <select x-model="form.campos_dinamicos.combustible" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-bold">
                                <option value="LLENO">Lleno</option>
                                <option value="3/4">3/4</option>
                                <option value="1/2">1/2</option>
                                <option value="1/4">1/4</option>
                                <option value="VACIO">Vacío / Reserva</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Color del Vehículo</label>
                            <input type="text" x-model="form.campos_dinamicos.color" class="w-full bg-white border border-blue-200 rounded-xl p-2" placeholder="Ej: Azul Metálico">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Año del Vehículo</label>
                            <input type="number" x-model.number="form.campos_dinamicos.anio" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-mono" placeholder="2020">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Objetos en Vehículo</label>
                            <input type="text" x-model="form.campos_dinamicos.objetos" class="w-full bg-white border border-blue-200 rounded-xl p-2" placeholder="Ej: Gato hidráulico, herramientas">
                        </div>
                    </div>

                    <!-- Electrodomésticos / Otros -->
                    <div x-show="form.tipo_servicio_id == 3" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Modelo / Referencia</label>
                            <input type="text" x-model="form.campos_dinamicos.modelo_ref" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-mono" placeholder="Número de modelo">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Voltaje</label>
                            <select x-model="form.campos_dinamicos.voltaje" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-bold">
                                <option value="110V">110V</option>
                                <option value="220V">220V</option>
                                <option value="DUAL">Dual 110/220V</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estado Físico</label>
                            <select x-model="form.campos_dinamicos.estado_fisico" class="w-full bg-white border border-blue-200 rounded-xl p-2 font-bold">
                                <option value="BUENO">Bueno</option>
                                <option value="REGULAR">Regular</option>
                                <option value="MALO">Malo</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Accesorios / Cable de poder</label>
                            <input type="text" x-model="form.campos_dinamicos.accesorios_propios" class="w-full bg-white border border-blue-200 rounded-xl p-2" placeholder="Control, cables, etc.">
                        </div>
                    </div>
                </div>

                <!-- Falla y observaciones -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Falla Reportada por el Cliente *</label>
                        <textarea x-model="form.falla_reportada_cliente" rows="3" required class="w-full border border-slate-200 rounded-xl p-2.5 resize-none bg-slate-50 focus:outline-none focus:border-blue-500" placeholder="Describa los síntomas o problemas reportados..."></textarea>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Accesorios / Objetos Recibidos</label>
                        <textarea x-model="form.accesorios_recibidos" rows="3" class="w-full border border-slate-200 rounded-xl p-2.5 resize-none bg-slate-50 focus:outline-none focus:border-blue-500" placeholder="Cargador, funda, llaves, manuales..."></textarea>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Diagnóstico Técnico Inicial</label>
                        <textarea x-model="form.diagnostico_tecnico" rows="2" class="w-full border border-slate-200 rounded-xl p-2.5 resize-none bg-slate-50 focus:outline-none focus:border-blue-500" placeholder="Observaciones iniciales del técnico..."></textarea>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Técnico Asignado</label>
                        <select x-model.number="form.tecnico_id" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-semibold">
                            <option value="">Sin asignar</option>
                            <template x-for="t in tecnicos" :key="t.id">
                                <option :value="t.id" x-text="t.nombre"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Botones -->
                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="modalNueva = false" class="px-5 py-2.5 font-bold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition">Cancelar</button>
                    <button type="submit" :disabled="guardando || !form.cliente_id" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-extrabold px-8 py-2.5 rounded-xl transition flex items-center gap-2 shadow">
                        <i class="fa-solid fa-clipboard-check" x-show="!guardando"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="guardando"></i>
                        <span x-text="guardando ? 'Creando orden...' : 'Crear Orden de Taller'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL: DETALLE / EDICIÓN DE ORDEN ===== -->
    <div x-show="modalDetalle && otSeleccionada" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-slate-100">
            <div class="bg-slate-900 text-white p-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center"><i class="fa-solid fa-wrench text-sm"></i></div>
                    <div>
                        <h3 class="font-bold text-sm" x-text="otSeleccionada?.numero_orden"></h3>
                        <p class="text-[10px] text-slate-400" x-text="otSeleccionada?.cliente_nombre"></p>
                    </div>
                </div>
                <button @click="modalDetalle = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-5 overflow-y-auto space-y-4 text-xs flex-1">
                <div class="bg-slate-50 rounded-2xl p-4 space-y-2 border border-slate-200">
                    <div class="flex justify-between">
                        <span class="font-bold text-slate-700">Falla reportada:</span>
                        <span class="font-bold text-slate-500" x-text="tipoLabel(otSeleccionada?.tipo_servicio_id)"></span>
                    </div>
                    <p class="text-slate-700 font-medium" x-text="otSeleccionada?.falla_reportada_cliente"></p>
                    <div x-show="otSeleccionada?.diagnostico_tecnico" class="border-t border-slate-200 pt-2">
                        <span class="font-bold text-slate-700 block mb-1">Diagnóstico técnico:</span>
                        <p class="text-slate-600" x-text="otSeleccionada?.diagnostico_tecnico"></p>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Actualizar Diagnóstico / Notas Técnicas</label>
                    <textarea x-model="otSeleccionada.diagnostico_tecnico" rows="3" class="w-full border border-slate-200 rounded-xl p-2.5 resize-none bg-slate-50 focus:outline-none focus:border-blue-500" placeholder="Diagnóstico actualizado..."></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Cambiar Estado</label>
                    <div class="flex gap-2 flex-wrap">
                        <template x-for="est in estados" :key="est.id">
                            <button type="button" @click="avanzarEstado(otSeleccionada, est.id)"
                                    class="px-3 py-1.5 rounded-xl font-bold border transition text-xs"
                                    :class="otSeleccionada.estado_id == est.id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:border-blue-400'"
                                    x-text="est.nombre">
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-3 border-t border-slate-100">
                    <button @click="actualizarOrden()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2 rounded-xl transition text-xs">
                        <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar Cambios
                    </button>
                    <button @click="modalDetalle = false; abrirLiquidar(otSeleccionada)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2 rounded-xl transition text-xs">
                        <i class="fa-solid fa-receipt mr-1"></i>Liquidar Orden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: LIQUIDACIÓN (Repuestos + Mano de Obra) ===== -->
    <div x-show="modalLiquidar && otSeleccionada" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-slate-100">
            <div class="bg-gradient-to-r from-emerald-900 to-emerald-700 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <div>
                        <h3 class="font-bold text-sm">Liquidar Orden: <span x-text="otSeleccionada?.numero_orden"></span></h3>
                        <p class="text-[10px] text-emerald-300">Repuestos del Kardex + Mano de Obra</p>
                    </div>
                </div>
                <button @click="modalLiquidar = false" class="text-emerald-300 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-4 text-xs">

                <!-- Búsqueda de Repuestos -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-slate-700 flex items-center gap-2"><i class="fa-solid fa-screwdriver-wrench text-amber-600"></i>Repuestos del Kardex</h4>
                        <div class="flex gap-2">
                            <input type="text" x-model="buscarRepuesto" @input.debounce.300ms="buscarEnKardex()"
                                   class="pl-3 pr-3 py-1.5 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:border-blue-500 w-48" placeholder="Buscar repuesto...">
                        </div>
                    </div>

                    <!-- Resultados de búsqueda -->
                    <div x-show="resultadosRepuesto.length > 0" class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-[11px]">
                            <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                                <tr><th class="p-2 text-left">Producto</th><th class="p-2 text-center">Stock</th><th class="p-2 text-right">Precio A</th><th class="p-2"></th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="r in resultadosRepuesto" :key="r.id">
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-2">
                                            <div class="font-bold" x-text="r.descripcion"></div>
                                            <div class="text-[10px] text-slate-400 font-mono" x-text="r.codigo"></div>
                                        </td>
                                        <td class="p-2 text-center font-mono font-bold" :class="Number(r.stock_total||0)>0?'text-emerald-600':'text-red-500'" x-text="r.stock_total"></td>
                                        <td class="p-2 text-right font-mono font-bold text-blue-700" x-text="'$' + Number(r.precio_a||0).toFixed(2)"></td>
                                        <td class="p-2">
                                            <button @click="agregarRepuesto(r)" :disabled="Number(r.stock_total||0)<=0" class="bg-blue-600 text-white px-2 py-1 rounded-lg font-bold text-[10px] hover:bg-blue-700 disabled:opacity-40 transition">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Repuestos seleccionados -->
                    <div x-show="liquidacion.repuestos.length > 0" class="border border-amber-100 rounded-xl overflow-hidden">
                        <div class="bg-amber-50 px-3 py-2 font-bold text-amber-800 text-[11px] uppercase">Repuestos a descargar del Kardex</div>
                        <table class="w-full text-[11px]">
                            <thead class="bg-slate-50 text-slate-600 font-bold text-[10px]">
                                <tr><th class="p-2 text-left">Repuesto</th><th class="p-2 text-center">Cantidad</th><th class="p-2 text-right">P. Unit.</th><th class="p-2 text-right">Subtotal</th><th class="p-2"></th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(rep, idx) in liquidacion.repuestos" :key="idx">
                                    <tr>
                                        <td class="p-2 font-bold" x-text="rep.descripcion"></td>
                                        <td class="p-2 text-center"><input type="number" min="1" x-model.number="rep.cantidad" class="w-14 border border-slate-200 rounded p-1 text-center font-mono font-bold focus:outline-none focus:border-blue-500"></td>
                                        <td class="p-2 text-right font-mono" x-text="'$' + Number(rep.precio_unitario).toFixed(2)"></td>
                                        <td class="p-2 text-right font-mono font-bold text-blue-700" x-text="'$' + (rep.cantidad * rep.precio_unitario).toFixed(2)"></td>
                                        <td class="p-2"><button @click="liquidacion.repuestos.splice(idx,1)" class="text-red-400 hover:text-red-600"><i class="fa-solid fa-trash-can"></i></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mano de Obra -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-slate-700 flex items-center gap-2"><i class="fa-solid fa-person-digging text-blue-600"></i>Mano de Obra</h4>
                        <button type="button" @click="liquidacion.mano_obra.push({descripcion:'',precio_unitario:0,cantidad:1})" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3 py-1.5 rounded-lg border border-slate-200 transition">
                            <i class="fa-solid fa-plus mr-1"></i>Agregar
                        </button>
                    </div>
                    <div x-show="liquidacion.mano_obra.length > 0" class="border border-blue-100 rounded-xl overflow-hidden">
                        <table class="w-full text-[11px]">
                            <thead class="bg-blue-50 text-blue-700 font-bold text-[10px]">
                                <tr><th class="p-2 text-left">Descripción del Servicio</th><th class="p-2 text-center">Cant.</th><th class="p-2 text-right">Precio (USD)</th><th class="p-2"></th></tr>
                            </thead>
                            <tbody class="divide-y divide-blue-50">
                                <template x-for="(mo, idx) in liquidacion.mano_obra" :key="idx">
                                    <tr>
                                        <td class="p-2"><input type="text" x-model="mo.descripcion" class="w-full border border-slate-200 rounded-lg p-1.5 focus:outline-none focus:border-blue-500" placeholder="Ej: Instalación de software, Cambio de batería..."></td>
                                        <td class="p-2 text-center"><input type="number" min="1" x-model.number="mo.cantidad" class="w-12 border border-slate-200 rounded p-1 text-center font-mono focus:outline-none focus:border-blue-500"></td>
                                        <td class="p-2 text-right"><input type="number" step="0.01" x-model.number="mo.precio_unitario" class="w-20 border border-slate-200 rounded-lg p-1 text-right font-mono font-bold text-blue-700 focus:outline-none focus:border-blue-500"></td>
                                        <td class="p-2"><button @click="liquidacion.mano_obra.splice(idx,1)" class="text-red-400 hover:text-red-600"><i class="fa-solid fa-trash-can"></i></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totales -->
                <div class="bg-slate-900 text-white rounded-2xl p-4 space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400">Subtotal Repuestos:</span>
                        <span class="font-mono font-bold" x-text="'$' + totalRepuestos().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400">Subtotal Mano de Obra:</span>
                        <span class="font-mono font-bold" x-text="'$' + totalManoObra().toFixed(2)"></span>
                    </div>
                    <div class="border-t border-slate-700 pt-2 flex justify-between">
                        <span class="font-bold text-sm">TOTAL USD:</span>
                        <span class="font-black font-mono text-xl text-emerald-400" x-text="'$' + (totalRepuestos() + totalManoObra()).toFixed(2)"></span>
                    </div>
                </div>

                <!-- Botón Facturar -->
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button @click="modalLiquidar = false" class="px-5 py-2.5 font-bold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition">Cancelar</button>
                    <button @click="ejecutarLiquidacion()" :disabled="liquidando || (liquidacion.repuestos.length === 0 && liquidacion.mano_obra.length === 0)"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold px-8 py-2.5 rounded-xl transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice-dollar" x-show="!liquidando"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="liquidando"></i>
                        <span x-text="liquidando ? 'Procesando...' : 'Generar Factura SAT'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function serviciosSatApp() {
    return {
        estados: [
            { id: 1, nombre: 'Recibido',         color: 'bg-slate-400',   siguiente_id: 2 },
            { id: 2, nombre: 'Diagnóstico',      color: 'bg-blue-500',    siguiente_id: 3 },
            { id: 3, nombre: 'Presupuestado',    color: 'bg-yellow-500',  siguiente_id: 4 },
            { id: 4, nombre: 'Aprobado',         color: 'bg-orange-500',  siguiente_id: 5 },
            { id: 5, nombre: 'Reparando',        color: 'bg-indigo-500',  siguiente_id: 6 },
            { id: 6, nombre: 'Listo/Espera',     color: 'bg-emerald-500', siguiente_id: 7 },
            { id: 7, nombre: 'Entregado',        color: 'bg-gray-400',    siguiente_id: null }
        ],
        tiposServicio: [
            { id: 1, nombre: '💻 Computación / Celulares' },
            { id: 2, nombre: '🚗 Taller Automotriz' },
            { id: 3, nombre: '🔌 Electrodomésticos / Otros' }
        ],
        ordenes: [], clientes: [], tecnicos: [],
        modalNueva: false, modalDetalle: false, modalLiquidar: false,
        guardando: false, liquidando: false,
        otSeleccionada: null,
        buscarRepuesto: '', resultadosRepuesto: [],
        form: {
            tipo_servicio_id: 1, cliente_id: '', tecnico_id: '',
            marca_modelo: '', serial_equipo: '',
            falla_reportada_cliente: '', accesorios_recibidos: '',
            diagnostico_tecnico: '', campos_dinamicos: {}
        },
        liquidacion: { repuestos: [], mano_obra: [] },

        async init() {
            await Promise.all([this.cargarClientes(), this.cargarOrdenes(), this.cargarTecnicos()]);
        },

        async cargarClientes() {
            try { this.clientes = (await (await fetch('/api/clientes')).json()).data || []; } catch(e) {}
        },
        async cargarTecnicos() {
            try { this.tecnicos = (await (await fetch('/api/nomina/empleados?rol=TECNICO')).json()).data || []; } catch(e) {}
        },
        async cargarOrdenes() {
            try { this.ordenes = (await (await fetch('/api/sat/ordenes')).json()).data || []; } catch(e) { this.ordenes = []; }
        },

        filtrarPorEstado(id) { return this.ordenes.filter(o => o.estado_id == id); },

        tipoLabel(id) {
            const ts = this.tiposServicio.find(t => t.id == id);
            return ts ? ts.nombre : 'Servicio #' + id;
        },
        estadoNombre(id) {
            const e = this.estados.find(e => e.id == id);
            return e ? e.nombre : '';
        },
        formatFecha(f) {
            if (!f) return '';
            return new Date(f).toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: '2-digit' });
        },

        abrirNueva() {
            this.form = { tipo_servicio_id: 1, cliente_id: '', tecnico_id: '', marca_modelo: '',
                serial_equipo: '', falla_reportada_cliente: '', accesorios_recibidos: '',
                diagnostico_tecnico: '', campos_dinamicos: {} };
            this.modalNueva = true;
        },

        abrirDetalle(ot) {
            this.otSeleccionada = { ...ot };
            this.modalDetalle = true;
        },

        abrirLiquidar(ot) {
            this.otSeleccionada = ot;
            this.liquidacion = { repuestos: [], mano_obra: [{ descripcion: 'Mano de Obra Técnica', precio_unitario: 0, cantidad: 1 }] };
            this.buscarRepuesto = '';
            this.resultadosRepuesto = [];
            this.modalDetalle = false;
            this.modalLiquidar = true;
        },

        async avanzarEstado(ot, nuevoEstadoId) {
            ot.estado_id = nuevoEstadoId;
            try {
                await fetch('/api/sat/cambiar-estado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ orden_id: ot.id, estado_id: nuevoEstadoId })
                });
            } catch(e) {}
        },

        async actualizarOrden() {
            try {
                const r = await fetch('/api/sat/actualizar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: this.otSeleccionada.id, diagnostico_tecnico: this.otSeleccionada.diagnostico_tecnico, estado_id: this.otSeleccionada.estado_id })
                });
                this.modalDetalle = false;
                await this.cargarOrdenes();
            } catch(e) {}
        },

        async guardarOrden() {
            this.guardando = true;
            try {
                const res = await fetch('/api/sat/crear', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...this.form, usuario_id: 1 })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalNueva = false;
                    await this.cargarOrdenes();
                } else {
                    alert('❌ Error: ' + (json.message || json.mensaje));
                }
            } catch(e) { alert('Error: ' + e.message); } finally { this.guardando = false; }
        },

        async buscarEnKardex() {
            if (this.buscarRepuesto.length < 2) { this.resultadosRepuesto = []; return; }
            try {
                const r = await fetch(`/api/maestros/productos?q=${encodeURIComponent(this.buscarRepuesto)}&limit=8`);
                this.resultadosRepuesto = (await r.json()).data || [];
            } catch(e) {}
        },

        agregarRepuesto(prod) {
            const existe = this.liquidacion.repuestos.find(r => r.producto_id === prod.id);
            if (existe) { existe.cantidad++; return; }
            this.liquidacion.repuestos.push({ producto_id: prod.id, descripcion: prod.descripcion, cantidad: 1, precio_unitario: Number(prod.precio_a || 0) });
        },

        totalRepuestos() { return this.liquidacion.repuestos.reduce((a, r) => a + (r.cantidad * r.precio_unitario), 0); },
        totalManoObra() { return this.liquidacion.mano_obra.reduce((a, m) => a + (m.cantidad * m.precio_unitario), 0); },

        async ejecutarLiquidacion() {
            this.liquidando = true;
            try {
                const payload = {
                    orden_id: this.otSeleccionada.id,
                    tipo_documento: 'FACTURA',
                    repuestos: this.liquidacion.repuestos,
                    mano_obra: this.liquidacion.mano_obra,
                    total: this.totalRepuestos() + this.totalManoObra(),
                    usuario_id: 1
                };
                const res = await fetch('/api/sat/facturar', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalLiquidar = false;
                    alert('✅ Orden liquidada y facturada correctamente.');
                    await this.cargarOrdenes();
                } else {
                    alert('❌ Error: ' + (json.message || json.mensaje));
                }
            } catch(e) { alert('Error: ' + e.message); } finally { this.liquidando = false; }
        }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>