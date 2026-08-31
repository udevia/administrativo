<?php
$pageTitle = 'Ficha Maestra de Proveedores - mi ERP';
$activeMenu = 'maestros_proveedores';
ob_start();
?>
<div class="space-y-4" x-data="maestroProveedoresApp()" x-cloak>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs">Compras & CxP</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Ficha Maestra de Proveedores</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Datos fiscales, condiciones de pago, cuentas bancarias, retenciones ISLR y estado de cuenta CxP.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="filtroBusqueda" @input.debounce.300ms="cargarProveedores()" placeholder="Buscar por RIF, Razón Social..." class="pl-9 pr-4 py-2.5 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:border-blue-500 w-64">
            </div>
            <button @click="nuevoProveedor()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-truck-field"></i> Nuevo Proveedor
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Proveedores Activos</span>
            <p class="text-2xl font-black font-mono text-slate-900 mt-1" x-text="proveedores.filter(p=>p.estado==1).length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-red-50 shadow-sm">
            <span class="text-[10px] font-bold text-red-400 uppercase">Saldo Total CxP ($)</span>
            <p class="text-xl font-black font-mono text-red-700 mt-1" x-text="'$' + fmt(proveedores.reduce((a,p)=>a+Number(p.saldo_actual||0),0))"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Con Retención ISLR</span>
            <p class="text-2xl font-black font-mono text-slate-900 mt-1" x-text="proveedores.filter(p=>parseFloat(p.retencion_islr_porcentaje||0)>0).length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Contribuyentes Especiales</span>
            <p class="text-2xl font-black font-mono text-slate-900 mt-1" x-text="proveedores.filter(p=>p.tipo_contribuyente==='especial').length"></p>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3">Código</th>
                        <th class="p-3">Razón Social</th>
                        <th class="p-3">RIF</th>
                        <th class="p-3">Contribuyente</th>
                        <th class="p-3">Teléfono / Email</th>
                        <th class="p-3">Condición Pago</th>
                        <th class="p-3 text-center">% ISLR</th>
                        <th class="p-3 text-right">Saldo CxP ($)</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-for="p in proveedores" :key="p.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 font-mono font-bold text-indigo-700" x-text="p.codigo"></td>
                            <td class="p-3">
                                <div class="font-bold text-slate-800" x-text="p.razon_social"></div>
                                <div class="text-[10px] text-slate-400 truncate max-w-xs" x-text="p.direccion_fiscal"></div>
                            </td>
                            <td class="p-3 font-mono font-bold text-slate-700" x-text="p.documento_fiscal"></td>
                            <td class="p-3">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                      :class="p.tipo_contribuyente === 'especial' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600'"
                                      x-text="p.tipo_contribuyente === 'especial' ? 'Especial' : 'Ordinario'"></span>
                            </td>
                            <td class="p-3">
                                <div x-text="p.telefono || '—'"></div>
                                <div class="text-[10px] text-slate-400" x-text="p.email || ''"></div>
                            </td>
                            <td class="p-3 font-semibold text-slate-600" x-text="p.condicion_pago || 'CONTADO'"></td>
                            <td class="p-3 text-center">
                                <span class="font-mono font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded" x-text="parseFloat(p.retencion_islr_porcentaje||0).toFixed(2) + '%'"></span>
                            </td>
                            <td class="p-3 text-right font-mono font-bold"
                                :class="Number(p.saldo_actual||0) > 0 ? 'text-red-600' : 'text-slate-400'"
                                x-text="'$' + fmt(p.saldo_actual)"></td>
                            <td class="p-3 text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="editarProveedor(p)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Editar ficha">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <a :href="'https://wa.me/' + (p.telefono||'').replace(/\D/g,'')" target="_blank" x-show="p.telefono"
                                       class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="WhatsApp">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="proveedores.length === 0 && !cargando">
                        <td colspan="9" class="p-12 text-center text-slate-400">
                            <i class="fa-solid fa-truck-field text-4xl text-slate-200 block mb-2"></i>
                            No se encontraron proveedores registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL FICHA PROVEEDOR -->
    <div x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[95vh]" @click.away="">
            <div class="bg-gradient-to-r from-slate-900 to-indigo-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-truck-field"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold" x-text="form.id ? 'Ficha Proveedor: ' + form.razon_social : 'Registrar Nuevo Proveedor'"></h2>
                        <p class="text-[10px] text-slate-400" x-text="form.documento_fiscal || 'Complete los datos fiscales'"></p>
                    </div>
                </div>
                <button @click="modalAbierto = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <!-- Pestañas -->
            <div class="border-b border-slate-200 bg-slate-50 px-5 flex gap-0 overflow-x-auto">
                <button @click="tabProv='fiscal'" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabProv==='fiscal' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-id-card mr-1.5"></i>Datos Fiscales
                </button>
                <button @click="tabProv='comercial'" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabProv==='comercial' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-handshake mr-1.5"></i>Condiciones & Retenciones
                </button>
                <button @click="tabProv='bancario'" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabProv==='bancario' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-university mr-1.5"></i>Datos Bancarios
                </button>
                <button @click="tabProv='contactos'" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabProv==='contactos' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-address-book mr-1.5"></i>Contactos
                </button>
                <button @click="tabProv='cxp'" x-show="form.id" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition"
                        :class="tabProv==='cxp' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-file-invoice mr-1.5"></i>Estado CxP
                </button>
            </div>

            <form @submit.prevent="guardar()" class="flex-1 overflow-y-auto">
                <!-- TAB: DATOS FISCALES -->
                <div x-show="tabProv==='fiscal'" class="p-6 text-xs space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Código</label>
                            <input type="text" x-model="form.codigo" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-indigo-700 focus:outline-none focus:border-indigo-500">
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
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block font-bold text-slate-700 mb-1">Razón Social *</label>
                            <input type="text" x-model="form.razon_social" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Persona de Contacto</label>
                            <input type="text" x-model="form.contacto_principal" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Teléfono</label>
                            <input type="text" x-model="form.telefono" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Email</label>
                            <input type="email" x-model="form.email" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block font-bold text-slate-700 mb-1">Dirección Fiscal *</label>
                            <textarea x-model="form.direccion_fiscal" rows="2" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 resize-none focus:outline-none focus:border-blue-500"></textarea>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estado</label>
                            <select x-model.number="form.estado" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TAB: CONDICIONES & RETENCIONES -->
                <div x-show="tabProv==='comercial'" class="p-6 text-xs space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Condición de Pago</label>
                            <select x-model="form.condicion_pago" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="CONTADO">Contado</option>
                                <option value="15D">15 días</option>
                                <option value="30D">30 días</option>
                                <option value="45D">45 días</option>
                                <option value="60D">60 días</option>
                                <option value="90D">90 días</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Moneda de Facturación</label>
                            <select x-model="form.moneda_facturacion" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="USD">USD (Dólares)</option>
                                <option value="VES">VES (Bolívares)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Descuento Comercial (%)</label>
                            <input type="number" step="0.01" x-model.number="form.descuento_comercial" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Retenciones -->
                    <div class="bg-orange-50 border border-orange-100 rounded-2xl p-4 space-y-4">
                        <h4 class="font-bold text-orange-900 flex items-center gap-2">
                            <i class="fa-solid fa-percent text-orange-600"></i> Retención de ISLR
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Concepto de Retención</label>
                                <select x-model="form.retencion_islr_concepto" class="w-full bg-white border border-orange-200 rounded-xl p-2.5 font-bold">
                                    <option value="">No Aplica</option>
                                    <option value="SERVICIOS_HONORARIOS">Honorarios Profesionales</option>
                                    <option value="SERVICIOS_TECNICOS">Servicios Técnicos</option>
                                    <option value="ARRENDAMIENTO">Arrendamiento de Bienes</option>
                                    <option value="COMISIONES">Comisiones Mercantiles</option>
                                    <option value="COMPRAS">Compras de Bienes</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Porcentaje ISLR (%)</label>
                                <input type="number" step="0.01" x-model.number="form.retencion_islr_porcentaje" class="w-full bg-white border border-orange-200 rounded-xl p-2.5 font-mono font-bold" placeholder="3.00">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Nº Comprobante ARI</label>
                                <input type="text" x-model="form.numero_comprobante_ari" class="w-full bg-white border border-orange-200 rounded-xl p-2.5 font-mono" placeholder="ARI-XXXXXXXX">
                            </div>
                        </div>
                    </div>

                    <!-- IVA Crédito Fiscal -->
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
                        <h4 class="font-bold text-blue-900 flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-receipt text-blue-600"></i> Créditos Fiscales IVA
                        </h4>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="form.genera_credito_fiscal_iva" class="rounded text-blue-600">
                                <span class="font-bold text-slate-700">Genera Crédito Fiscal IVA (Proveedor Ordinario)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- TAB: DATOS BANCARIOS -->
                <div x-show="tabProv==='bancario'" class="p-6 text-xs space-y-4">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-bold text-slate-700">Cuentas Bancarias para Pagos</h4>
                        <button type="button" @click="agregarCuentaBancaria()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3 py-1.5 rounded-lg border border-slate-200 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar Cuenta
                        </button>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(cb, i) in form.cuentas_bancarias" :key="i">
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 relative">
                                <button type="button" @click="form.cuentas_bancarias.splice(i,1)" class="absolute top-3 right-3 text-slate-300 hover:text-red-500 transition">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Banco</label>
                                        <input type="text" x-model="cb.banco" class="w-full bg-white border border-slate-200 rounded-xl p-2 focus:outline-none focus:border-blue-500" placeholder="Ej: Banesco, BDV">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Nº Cuenta</label>
                                        <input type="text" x-model="cb.numero_cuenta" class="w-full bg-white border border-slate-200 rounded-xl p-2 font-mono focus:outline-none focus:border-blue-500" placeholder="XXXX-XXXX-XXXX">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Tipo</label>
                                        <select x-model="cb.tipo_cuenta" class="w-full bg-white border border-slate-200 rounded-xl p-2 font-bold">
                                            <option value="CORRIENTE">Corriente</option>
                                            <option value="AHORRO">Ahorro</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Moneda</label>
                                        <select x-model="cb.moneda" class="w-full bg-white border border-slate-200 rounded-xl p-2 font-bold">
                                            <option value="VES">VES (Bolívares)</option>
                                            <option value="USD">USD (Dólares)</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block font-bold text-slate-600 mb-1">Titular de la Cuenta (si difiere de la Razón Social)</label>
                                        <input type="text" x-model="cb.titular" class="w-full bg-white border border-slate-200 rounded-xl p-2 focus:outline-none focus:border-blue-500" placeholder="Nombre del titular...">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="form.cuentas_bancarias.length === 0" class="text-center py-8 text-slate-400 border-2 border-dashed border-slate-200 rounded-2xl">
                            <i class="fa-solid fa-university text-2xl block mb-2 text-slate-300"></i>
                            No hay cuentas bancarias registradas para este proveedor.
                        </div>
                    </div>
                </div>

                <!-- TAB: CONTACTOS -->
                <div x-show="tabProv==='contactos'" class="p-6 text-xs space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-slate-700">Personas de Contacto</h4>
                        <button type="button" @click="agregarContactoProv()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3 py-1.5 rounded-lg border border-slate-200 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(ct, i) in form.contactos" :key="i">
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 relative">
                                <button type="button" @click="form.contactos.splice(i,1)" class="absolute top-3 right-3 text-slate-300 hover:text-red-500">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Nombre</label>
                                        <input type="text" x-model="ct.nombre" class="w-full bg-white border border-slate-200 rounded-xl p-2 focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Cargo</label>
                                        <input type="text" x-model="ct.cargo" class="w-full bg-white border border-slate-200 rounded-xl p-2 focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Teléfono / WhatsApp</label>
                                        <input type="text" x-model="ct.telefono" class="w-full bg-white border border-slate-200 rounded-xl p-2 font-mono focus:outline-none focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="form.contactos.length === 0" class="text-center py-8 text-slate-400 border-2 border-dashed border-slate-200 rounded-2xl">
                            <i class="fa-solid fa-address-book text-2xl block mb-2 text-slate-300"></i>
                            Sin contactos registrados.
                        </div>
                    </div>
                </div>

                <!-- TAB: ESTADO CxP -->
                <div x-show="tabProv==='cxp'" class="p-6 text-xs space-y-3">
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-red-50 border border-red-100 rounded-2xl p-3 text-center">
                            <span class="text-[10px] font-bold text-red-500 uppercase block">Saldo CxP Total</span>
                            <span class="font-mono font-black text-red-900 text-lg" x-text="'$' + fmt(form.saldo_actual)"></span>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase block">Facturas Pendientes</span>
                            <span class="font-mono font-black text-slate-900 text-lg" x-text="cxpDocumentos.length"></span>
                        </div>
                    </div>
                    <table class="w-full border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                            <tr><th class="p-2">Factura</th><th class="p-2">Fecha</th><th class="p-2">Vence</th><th class="p-2 text-right">Monto ($)</th><th class="p-2 text-right">Saldo ($)</th><th class="p-2">Estado</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="d in cxpDocumentos" :key="d.id">
                                <tr>
                                    <td class="p-2 font-mono font-bold text-indigo-700" x-text="d.numero_factura || d.referencia"></td>
                                    <td class="p-2 font-mono" x-text="d.fecha_emision"></td>
                                    <td class="p-2 font-mono" :class="d.vencido?'text-red-600 font-bold':''" x-text="d.fecha_vencimiento||'—'"></td>
                                    <td class="p-2 text-right font-mono" x-text="'$' + fmt(d.monto_total)"></td>
                                    <td class="p-2 text-right font-mono font-bold text-red-600" x-text="'$' + fmt(d.saldo_pendiente)"></td>
                                    <td class="p-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="d.vencido?'bg-red-100 text-red-700':'bg-blue-100 text-blue-700'" x-text="d.vencido?'Vencido':'Vigente'"></span></td>
                                </tr>
                            </template>
                            <tr x-show="cxpDocumentos.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">Sin facturas CxP pendientes.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Botones -->
                <div class="p-5 border-t border-slate-100 flex justify-end gap-2 bg-slate-50/50">
                    <button type="button" @click="modalAbierto = false" class="px-5 py-2 text-slate-600 hover:text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition">Cancelar</button>
                    <button type="submit" :disabled="guardando" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-extrabold px-8 py-2.5 rounded-xl transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk" x-show="!guardando"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="guardando"></i>
                        <span x-text="guardando ? 'Guardando...' : 'Guardar Proveedor'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function maestroProveedoresApp() {
    return {
        proveedores: [], cargando: false, guardando: false, modalAbierto: false,
        filtroBusqueda: '', tabProv: 'fiscal', cxpDocumentos: [],
        form: {
            id: null, codigo: '', razon_social: '', documento_fiscal: '',
            tipo_persona: 'juridica', tipo_contribuyente: 'ordinario', direccion_fiscal: '',
            contacto_principal: '', telefono: '', email: '', condicion_pago: 'CONTADO',
            moneda_facturacion: 'USD', descuento_comercial: 0,
            retencion_islr_concepto: '', retencion_islr_porcentaje: 0, numero_comprobante_ari: '',
            genera_credito_fiscal_iva: true, estado: 1, saldo_actual: 0,
            cuentas_bancarias: [], contactos: []
        },

        async init() { await this.cargarProveedores(); },

        async cargarProveedores() {
            this.cargando = true;
            try {
                const res = await fetch(`/api/maestros/proveedores?q=${encodeURIComponent(this.filtroBusqueda)}`);
                this.proveedores = (await res.json()).data || [];
            } catch(e) { this.proveedores = []; } finally { this.cargando = false; }
        },

        nuevoProveedor() {
            this.form = {
                id: null, codigo: 'PRV-' + String(this.proveedores.length + 1).padStart(4,'0'),
                razon_social: '', documento_fiscal: '', tipo_persona: 'juridica',
                tipo_contribuyente: 'ordinario', direccion_fiscal: '', contacto_principal: '',
                telefono: '', email: '', condicion_pago: 'CONTADO', moneda_facturacion: 'USD',
                descuento_comercial: 0, retencion_islr_concepto: '', retencion_islr_porcentaje: 0,
                numero_comprobante_ari: '', genera_credito_fiscal_iva: true, estado: 1,
                saldo_actual: 0, cuentas_bancarias: [], contactos: []
            };
            this.tabProv = 'fiscal';
            this.cxpDocumentos = [];
            this.modalAbierto = true;
        },

        editarProveedor(p) {
            this.form = { ...JSON.parse(JSON.stringify(p)), cuentas_bancarias: p.cuentas_bancarias || [], contactos: p.contactos || [] };
            this.tabProv = 'fiscal';
            this.cxpDocumentos = [];
            this.modalAbierto = true;
            // Load CxP docs if editing
            if (p.id) this.cargarCxP(p.id);
        },

        async cargarCxP(id) {
            try {
                const r = await fetch(`/api/maestros/proveedores/${id}/cxp`);
                this.cxpDocumentos = (await r.json()).data || [];
            } catch(e) {}
        },

        agregarCuentaBancaria() {
            this.form.cuentas_bancarias.push({ banco: '', numero_cuenta: '', tipo_cuenta: 'CORRIENTE', moneda: 'VES', titular: '' });
        },

        agregarContactoProv() {
            this.form.contactos.push({ nombre: '', cargo: '', telefono: '' });
        },

        async guardar() {
            this.guardando = true;
            try {
                const res = await fetch('/api/maestros/proveedores', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalAbierto = false;
                    await this.cargarProveedores();
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
