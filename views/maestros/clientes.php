<?php
$pageTitle = 'Ficha Maestra de Clientes - mi ERP';
$activeMenu = 'maestros_clientes';
ob_start();
?>
<div class="space-y-4" x-data="maestroClientesApp()" x-cloak>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs">Ventas & CxC</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Ficha Maestra de Clientes</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Cartera completa: datos fiscales, crédito, retenciones, contactos y estado de cuenta.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="filtroBusqueda" @input.debounce.300ms="cargarClientes()" placeholder="Buscar por RIF, Razón Social..." class="pl-9 pr-4 py-2.5 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:border-blue-500 w-64">
            </div>
            <select x-model="filtroEstado" @change="cargarClientes()" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-semibold">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
            <button @click="nuevoCliente()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-user-plus"></i> Nuevo Cliente
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Clientes Activos</span>
            <p class="text-2xl font-black font-mono text-slate-900 mt-1" x-text="clientes.filter(c=>c.estado==1).length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-amber-100 shadow-sm">
            <span class="text-[10px] font-bold text-amber-500 uppercase">Saldo Total CxC ($)</span>
            <p class="text-xl font-black font-mono text-amber-700 mt-1" x-text="'$' + fmt(clientes.reduce((a,c)=>a+Number(c.saldo_actual||0),0))"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Con Crédito Activo</span>
            <p class="text-2xl font-black font-mono text-slate-900 mt-1" x-text="clientes.filter(c=>c.permite_credito).length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-red-50 shadow-sm">
            <span class="text-[10px] font-bold text-red-400 uppercase">Agentes de Ret. IVA</span>
            <p class="text-2xl font-black font-mono text-red-700 mt-1" x-text="clientes.filter(c=>c.aplica_retencion_iva).length"></p>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3">Código</th>
                        <th class="p-3">Razón Social / Nombre</th>
                        <th class="p-3">RIF / Cédula</th>
                        <th class="p-3">Tipo</th>
                        <th class="p-3">Teléfono / Email</th>
                        <th class="p-3">Zona / Vendedor</th>
                        <th class="p-3 text-center">Tarifa</th>
                        <th class="p-3 text-right">Límite ($)</th>
                        <th class="p-3 text-right">Saldo CxC ($)</th>
                        <th class="p-3 text-center">Est.</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-for="c in clientes" :key="c.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 font-mono font-bold text-emerald-700" x-text="c.codigo"></td>
                            <td class="p-3">
                                <div class="font-bold text-slate-800" x-text="c.razon_social"></div>
                                <div class="text-[10px] text-slate-400" x-show="c.nombre_comercial" x-text="c.nombre_comercial"></div>
                            </td>
                            <td class="p-3 font-mono font-bold text-slate-700" x-text="c.documento_fiscal"></td>
                            <td class="p-3">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                      :class="c.tipo_contribuyente === 'especial' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600'"
                                      x-text="c.tipo_contribuyente === 'especial' ? 'Esp.' : 'Ord.'"></span>
                            </td>
                            <td class="p-3">
                                <div x-text="c.telefono || '—'"></div>
                                <div class="text-[10px] text-slate-400" x-text="c.email || ''"></div>
                            </td>
                            <td class="p-3">
                                <div class="font-semibold" x-text="c.zona_nombre || 'Principal'"></div>
                                <div class="text-[10px] text-slate-400" x-text="c.vendedor_nombre || 'Directo'"></div>
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded font-mono font-bold bg-blue-50 text-blue-700" x-text="'Lista ' + (c.lista_precio_default || 'A')"></span>
                            </td>
                            <td class="p-3 text-right font-mono" x-text="'$' + fmt(c.limite_credito)"></td>
                            <td class="p-3 text-right font-mono font-bold"
                                :class="Number(c.saldo_actual||0) > 0 ? 'text-amber-600' : 'text-slate-400'"
                                x-text="'$' + fmt(c.saldo_actual)"></td>
                            <td class="p-3 text-center">
                                <span class="w-5 h-5 inline-flex items-center justify-center rounded-full text-[10px] font-black"
                                      :class="c.estado == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                                    <i :class="c.estado == 1 ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
                                </span>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="editarCliente(c)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Editar ficha">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <a :href="'https://wa.me/' + (c.telefono || '').replace(/\D/g,'')" target="_blank"
                                       x-show="c.telefono"
                                       class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="WhatsApp">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                    <button @click="verCxC(c)" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Estado de cuenta CxC">
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="clientes.length === 0 && !cargando">
                        <td colspan="11" class="p-12 text-center text-slate-400">
                            <i class="fa-solid fa-users text-4xl text-slate-200 block mb-2"></i>
                            No se encontraron clientes registrados.
                        </td>
                    </tr>
                    <tr x-show="cargando">
                        <td colspan="11" class="p-8 text-center text-slate-400">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL FICHA DE CLIENTE (pestañas) -->
    <div x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[95vh]" @click.away="">
            <!-- Header -->
            <div class="bg-gradient-to-r from-slate-900 to-emerald-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-building-user"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold" x-text="form.id ? 'Ficha de Cliente: ' + form.razon_social : 'Registrar Nuevo Cliente'"></h2>
                        <p class="text-[10px] text-slate-400" x-text="form.documento_fiscal || 'Complete los datos fiscales'"></p>
                    </div>
                </div>
                <button @click="modalAbierto = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <!-- Pestañas -->
            <div class="border-b border-slate-200 bg-slate-50 px-5 flex gap-0 overflow-x-auto">
                <button @click="tabCliente='general'" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabCliente==='general' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-id-card mr-1.5"></i>Datos Fiscales
                </button>
                <button @click="tabCliente='comercial'" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabCliente==='comercial' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-handshake mr-1.5"></i>Comercial & Crédito
                </button>
                <button @click="tabCliente='contactos'" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabCliente==='contactos' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-address-book mr-1.5"></i>Contactos
                </button>
                <button @click="tabCliente='cxc'" x-show="form.id" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabCliente==='cxc' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-file-invoice-dollar mr-1.5"></i>Estado de Cuenta CxC
                </button>
            </div>

            <form @submit.prevent="guardar()" class="flex-1 overflow-y-auto">
                <!-- TAB: DATOS FISCALES -->
                <div x-show="tabCliente==='general'" class="p-6 text-xs space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Código Cliente</label>
                            <input type="text" x-model="form.codigo" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-emerald-700 focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">RIF / Cédula *</label>
                            <input type="text" x-model="form.documento_fiscal" required placeholder="J-12345678-0" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tipo Contribuyente</label>
                            <select x-model="form.tipo_contribuyente" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="ordinario">Ordinario</option>
                                <option value="especial">Especial (Gran Contribuyente)</option>
                                <option value="formal">Formal (Simplificado)</option>
                                <option value="no_sujeto">No Sujeto</option>
                                <option value="exento">Exento</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Razón Social / Nombre Completo *</label>
                            <input type="text" x-model="form.razon_social" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nombre Comercial</label>
                            <input type="text" x-model="form.nombre_comercial" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tipo de Persona</label>
                            <select x-model="form.tipo_persona" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="juridica">Jurídica (Empresa)</option>
                                <option value="natural">Natural (Persona)</option>
                                <option value="gobierno">Organismo Gubernamental</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Teléfono Principal</label>
                            <input type="text" x-model="form.telefono" placeholder="+58 4XX XXX XXXX" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Correo Electrónico</label>
                            <input type="email" x-model="form.email" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block font-bold text-slate-700 mb-1">Dirección Fiscal Completa *</label>
                            <textarea x-model="form.direccion_fiscal" rows="2" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estado de la Ficha</label>
                            <select x-model.number="form.estado" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo / Bloqueado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TAB: COMERCIAL & CRÉDITO -->
                <div x-show="tabCliente==='comercial'" class="p-6 text-xs space-y-5">
                    <!-- Asignación comercial -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Lista de Precios Default</label>
                            <select x-model="form.lista_precio_default" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold font-mono">
                                <option value="A">Lista A — PVP / Consumidor Final</option>
                                <option value="B">Lista B — Mayor / Distribuidor</option>
                                <option value="C">Lista C — Corporativo</option>
                                <option value="D">Lista D — Especial Negociado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Zona Geográfica</label>
                            <select x-model.number="form.zona_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                                <option value="">-- Sin zona asignada --</option>
                                <template x-for="z in zonas" :key="z.id">
                                    <option :value="z.id" x-text="z.descripcion"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Vendedor Asignado</label>
                            <select x-model.number="form.vendedor_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                                <option value="">Oficina / Venta Directa</option>
                                <template x-for="v in vendedores" :key="v.id">
                                    <option :value="v.id" x-text="v.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Descuento Comercial (%)</label>
                            <input type="number" step="0.01" min="0" max="100" x-model.number="form.descuento_comercial" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Condición de Pago Default</label>
                            <select x-model="form.condicion_pago" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="CONTADO">Contado</option>
                                <option value="15D">15 días</option>
                                <option value="30D">30 días</option>
                                <option value="60D">60 días</option>
                                <option value="90D">90 días</option>
                            </select>
                        </div>
                    </div>

                    <!-- Crédito -->
                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-blue-900 flex items-center gap-2">
                                <i class="fa-solid fa-credit-card text-blue-600"></i> Política de Crédito
                            </h4>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <span class="text-xs font-bold text-slate-700">Habilitado</span>
                                <div class="relative">
                                    <input type="checkbox" x-model="form.permite_credito" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-300 peer-checked:bg-blue-600 rounded-full transition peer-focus:ring-2 peer-focus:ring-blue-300"></div>
                                    <div class="absolute top-0.5 left-0.5 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-4 shadow"></div>
                                </div>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3" :class="!form.permite_credito && 'opacity-40 pointer-events-none'">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Límite de Crédito (USD)</label>
                                <input type="number" step="0.01" x-model.number="form.limite_credito" class="w-full bg-white border border-blue-200 rounded-xl p-2.5 font-mono font-bold text-blue-900">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Días de Crédito</label>
                                <input type="number" x-model.number="form.dias_credito" class="w-full bg-white border border-blue-200 rounded-xl p-2.5 font-mono font-bold">
                            </div>
                        </div>
                    </div>

                    <!-- Retenciones Fiscales -->
                    <div class="bg-red-50 border border-red-100 rounded-2xl p-4 space-y-3">
                        <h4 class="font-bold text-red-900 flex items-center gap-2">
                            <i class="fa-solid fa-percent text-red-600"></i> Retenciones Fiscales Aplicables
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="bg-white p-3 rounded-xl border border-red-100 space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.aplica_retencion_iva" class="rounded text-red-600">
                                    <span class="font-bold text-slate-800">Agente Retención IVA</span>
                                </label>
                                <div class="ml-5" x-show="form.aplica_retencion_iva">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Porcentaje (%)</label>
                                    <select x-model.number="form.porcentaje_retencion_iva" class="w-full text-xs border border-slate-200 rounded-lg p-1.5 font-mono">
                                        <option value="75">75% del IVA</option>
                                        <option value="100">100% del IVA</option>
                                    </select>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-red-100 space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.aplica_retencion_islr" class="rounded text-red-600">
                                    <span class="font-bold text-slate-800">Aplica Retención ISLR</span>
                                </label>
                                <div class="ml-5" x-show="form.aplica_retencion_islr">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">% ISLR</label>
                                    <input type="number" step="0.01" x-model.number="form.porcentaje_retencion_islr" class="w-full text-xs border border-slate-200 rounded-lg p-1.5 font-mono" placeholder="3.00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: CONTACTOS MÚLTIPLES -->
                <div x-show="tabCliente==='contactos'" class="p-6 text-xs space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-slate-700">Personas de Contacto</h4>
                        <button type="button" @click="agregarContacto()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3 py-1.5 rounded-lg border border-slate-200 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar Contacto
                        </button>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(ct, i) in form.contactos" :key="i">
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 relative">
                                <button type="button" @click="form.contactos.splice(i,1)" class="absolute top-3 right-3 text-slate-300 hover:text-red-500 transition">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Nombre Completo</label>
                                        <input type="text" x-model="ct.nombre" class="w-full bg-white border border-slate-200 rounded-xl p-2 focus:outline-none focus:border-blue-500" placeholder="Ej: Juan Pérez">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Cargo / Departamento</label>
                                        <input type="text" x-model="ct.cargo" class="w-full bg-white border border-slate-200 rounded-xl p-2 focus:outline-none focus:border-blue-500" placeholder="Ej: Compras, Tesorería">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Teléfono / WhatsApp</label>
                                        <input type="text" x-model="ct.telefono" class="w-full bg-white border border-slate-200 rounded-xl p-2 font-mono focus:outline-none focus:border-blue-500" placeholder="+58 4XX XXX XXXX">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Email Directo</label>
                                        <input type="email" x-model="ct.email" class="w-full bg-white border border-slate-200 rounded-xl p-2 focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="ct.recibe_facturas" class="rounded text-blue-600">
                                            <span class="font-semibold text-slate-700">Recibe facturas por email</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="form.contactos.length === 0" class="text-center py-8 text-slate-400 border-2 border-dashed border-slate-200 rounded-2xl">
                            <i class="fa-solid fa-address-book text-2xl block mb-2 text-slate-300"></i>
                            No hay contactos registrados. Agregue personas de contacto clave.
                        </div>
                    </div>
                </div>

                <!-- TAB: ESTADO DE CUENTA CxC -->
                <div x-show="tabCliente==='cxc'" class="p-6 text-xs space-y-3">
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3 text-center">
                            <span class="text-[10px] font-bold text-amber-600 uppercase block">Saldo Total</span>
                            <span class="font-mono font-black text-amber-900 text-lg" x-text="'$' + fmt(form.saldo_actual)"></span>
                        </div>
                        <div class="bg-red-50 border border-red-100 rounded-2xl p-3 text-center">
                            <span class="text-[10px] font-bold text-red-500 uppercase block">Vencido</span>
                            <span class="font-mono font-black text-red-700 text-lg" x-text="'$' + fmt(cxcVencido)"></span>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase block">Por Vencer</span>
                            <span class="font-mono font-black text-slate-900 text-lg" x-text="'$' + fmt(Number(form.saldo_actual||0) - cxcVencido)"></span>
                        </div>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="p-2">Documento</th>
                                <th class="p-2">Fecha</th>
                                <th class="p-2">Vence</th>
                                <th class="p-2 text-right">Monto ($)</th>
                                <th class="p-2 text-right">Saldo ($)</th>
                                <th class="p-2 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="doc in cxcDocumentos" :key="doc.id">
                                <tr>
                                    <td class="p-2 font-mono font-bold text-blue-700" x-text="doc.numero_factura || doc.referencia"></td>
                                    <td class="p-2 font-mono text-slate-600" x-text="doc.fecha_emision"></td>
                                    <td class="p-2 font-mono" :class="doc.vencido ? 'text-red-600 font-bold' : 'text-slate-600'" x-text="doc.fecha_vencimiento || '—'"></td>
                                    <td class="p-2 text-right font-mono" x-text="'$' + fmt(doc.monto_total)"></td>
                                    <td class="p-2 text-right font-mono font-bold text-amber-700" x-text="'$' + fmt(doc.saldo_pendiente)"></td>
                                    <td class="p-2 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                              :class="doc.vencido ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'"
                                              x-text="doc.vencido ? 'Vencido' : 'Vigente'"></span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="cxcDocumentos.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">Sin documentos de crédito pendientes.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Botones globales del form -->
                <div class="p-5 border-t border-slate-100 flex justify-end gap-2 bg-slate-50/50">
                    <button type="button" @click="modalAbierto = false" class="px-5 py-2 text-slate-600 hover:text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition">Cancelar</button>
                    <button type="submit" :disabled="guardando" class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold px-8 py-2.5 rounded-xl transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk" x-show="!guardando"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="guardando"></i>
                        <span x-text="guardando ? 'Guardando...' : 'Guardar Cliente'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CxC -->
    <div x-show="modalCxC" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[80vh] flex flex-col" @click.away="modalCxC=false">
            <div class="bg-slate-900 text-white p-5 flex justify-between items-center">
                <h2 class="font-bold" x-text="'Estado CxC: ' + (clienteCxC?.razon_social || '')"></h2>
                <button @click="modalCxC=false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="overflow-y-auto flex-1 p-5 text-xs">
                <table class="w-full border-collapse">
                    <thead class="bg-slate-100 font-bold text-slate-600 uppercase text-[10px]">
                        <tr><th class="p-2">Documento</th><th class="p-2">Fecha</th><th class="p-2 text-right">Monto</th><th class="p-2 text-right">Saldo</th><th class="p-2">Estado</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="d in cxcDocumentos" :key="d.id">
                            <tr>
                                <td class="p-2 font-mono font-bold text-blue-700" x-text="d.numero_factura || d.referencia"></td>
                                <td class="p-2 font-mono" x-text="d.fecha_emision"></td>
                                <td class="p-2 text-right font-mono" x-text="'$' + fmt(d.monto_total)"></td>
                                <td class="p-2 text-right font-mono font-bold text-amber-700" x-text="'$' + fmt(d.saldo_pendiente)"></td>
                                <td class="p-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="d.vencido?'bg-red-100 text-red-700':'bg-blue-100 text-blue-700'" x-text="d.vencido?'Vencido':'Vigente'"></span></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function maestroClientesApp() {
    return {
        clientes: [],
        zonas: [],
        vendedores: [],
        cargando: false,
        guardando: false,
        modalAbierto: false,
        modalCxC: false,
        clienteCxC: null,
        cxcDocumentos: [],
        tabCliente: 'general',
        filtroBusqueda: '',
        filtroEstado: '1',
        form: {
            id: null, codigo: '', razon_social: '', nombre_comercial: '', documento_fiscal: '',
            tipo_persona: 'juridica', tipo_contribuyente: 'ordinario', direccion_fiscal: '',
            telefono: '', email: '', zona_id: '', vendedor_id: '', lista_precio_default: 'A',
            descuento_comercial: 0, condicion_pago: 'CONTADO',
            limite_credito: 0, dias_credito: 0, permite_credito: 0,
            aplica_retencion_iva: 0, porcentaje_retencion_iva: 75,
            aplica_retencion_islr: 0, porcentaje_retencion_islr: 3,
            estado: 1, saldo_actual: 0, contactos: []
        },

        async init() {
            await this.cargarCatalogosAux();
            await this.cargarClientes();
        },

        async cargarCatalogosAux() {
            try {
                const [rZ, rV] = await Promise.all([fetch('/api/maestros/zonas'), fetch('/api/maestros/vendedores')]);
                this.zonas = (await rZ.json()).data || [];
                this.vendedores = (await rV.json()).data || [];
            } catch(e) {}
        },

        async cargarClientes() {
            this.cargando = true;
            try {
                const res = await fetch(`/api/maestros/clientes?q=${encodeURIComponent(this.filtroBusqueda)}&estado=${this.filtroEstado}`);
                this.clientes = (await res.json()).data || [];
            } catch(e) { this.clientes = []; } finally { this.cargando = false; }
        },

        nuevoCliente() {
            this.form = {
                id: null, codigo: 'CLI-' + String(this.clientes.length + 1).padStart(4,'0'),
                razon_social: '', nombre_comercial: '', documento_fiscal: '',
                tipo_persona: 'juridica', tipo_contribuyente: 'ordinario', direccion_fiscal: '',
                telefono: '', email: '', zona_id: this.zonas[0]?.id || '',
                vendedor_id: '', lista_precio_default: 'A', descuento_comercial: 0,
                condicion_pago: 'CONTADO', limite_credito: 0, dias_credito: 0, permite_credito: 0,
                aplica_retencion_iva: 0, porcentaje_retencion_iva: 75,
                aplica_retencion_islr: 0, porcentaje_retencion_islr: 3,
                estado: 1, saldo_actual: 0, contactos: []
            };
            this.tabCliente = 'general';
            this.modalAbierto = true;
        },

        editarCliente(c) {
            this.form = { ...JSON.parse(JSON.stringify(c)), contactos: c.contactos || [] };
            this.tabCliente = 'general';
            this.cxcDocumentos = [];
            this.modalAbierto = true;
        },

        agregarContacto() {
            this.form.contactos.push({ nombre: '', cargo: '', telefono: '', email: '', recibe_facturas: false });
        },

        async verCxC(c) {
            this.clienteCxC = c;
            this.cxcDocumentos = [];
            this.modalCxC = true;
            try {
                const r = await fetch(`/api/maestros/clientes/${c.id}/cxc`);
                this.cxcDocumentos = (await r.json()).data || [];
            } catch(e) {}
        },

        get cxcVencido() {
            return this.cxcDocumentos.filter(d => d.vencido).reduce((a,d) => a + Number(d.saldo_pendiente||0), 0);
        },

        async guardar() {
            this.guardando = true;
            try {
                const res = await fetch('/api/maestros/clientes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalAbierto = false;
                    await this.cargarClientes();
                } else {
                    alert('❌ Error: ' + (json.mensaje || json.message));
                }
            } catch(e) { alert('Error: ' + e.message); } finally { this.guardando = false; }
        },

        fmt(v) { return Number(v||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
