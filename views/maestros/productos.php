<?php
$pageTitle = 'Catálogo Maestro de Productos - mi ERP';
$activeMenu = 'maestros_productos';
ob_start();
?>
<div class="space-y-4" x-data="maestroProductosApp()" x-cloak>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs">Inventario & Catálogo</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Catálogo Maestro de Productos</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Artículos, múltiples presentaciones SKU, trazabilidad FEFO/Serial, precios bimonetarios y existencias multialmacén.</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Tasa activa -->
            <div class="px-3 py-2 bg-blue-50 border border-blue-200 rounded-xl text-xs font-mono font-bold text-blue-700">
                <i class="fa-solid fa-dollar-sign"></i> Bs. <span x-text="fmt2(tasaOficial)"></span>
            </div>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="filtroBusqueda" @input.debounce.300ms="cargarProductos()" placeholder="Código, descripción, barra..." class="pl-9 pr-4 py-2.5 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:border-blue-500 w-56">
            </div>
            <select x-model="filtroDepto" @change="filtrarFamilias(); cargarProductos()" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50 font-semibold">
                <option value="">Todos los Deptos</option>
                <template x-for="d in departamentos" :key="d.id">
                    <option :value="d.id" x-text="d.nombre"></option>
                </template>
            </select>
            <button @click="nuevoProducto()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Total Artículos</span>
            <p class="text-2xl font-black font-mono text-slate-900 mt-1" x-text="productos.length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-emerald-50 shadow-sm">
            <span class="text-[10px] font-bold text-emerald-500 uppercase">Con Stock</span>
            <p class="text-2xl font-black font-mono text-emerald-700 mt-1" x-text="productos.filter(p=>Number(p.stock_total||0)>0).length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-red-50 shadow-sm">
            <span class="text-[10px] font-bold text-red-400 uppercase">Sin Stock</span>
            <p class="text-2xl font-black font-mono text-red-700 mt-1" x-text="productos.filter(p=>Number(p.stock_total||0)<=0).length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-amber-50 shadow-sm">
            <span class="text-[10px] font-bold text-amber-500 uppercase">Con Lotes FEFO</span>
            <p class="text-2xl font-black font-mono text-amber-700 mt-1" x-text="productos.filter(p=>p.maneja_lotes==1).length"></p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-purple-50 shadow-sm">
            <span class="text-[10px] font-bold text-purple-500 uppercase">Con Seriales</span>
            <p class="text-2xl font-black font-mono text-purple-700 mt-1" x-text="productos.filter(p=>p.maneja_seriales==1).length"></p>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-3">Código / Barra</th>
                        <th class="p-3">Descripción</th>
                        <th class="p-3">Depto / Familia / Cat.</th>
                        <th class="p-3 text-center">SKUs</th>
                        <th class="p-3 text-right">Costo (USD)</th>
                        <th class="p-3 text-right">Precio A (PVP)</th>
                        <th class="p-3 text-right">Precio A (VES)</th>
                        <th class="p-3 text-center">Stock Total</th>
                        <th class="p-3 text-center">Flags</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-for="p in productos" :key="p.id">
                        <tr class="hover:bg-slate-50/80 transition" :class="Number(p.stock_total||0) <= 0 ? 'bg-red-50/20' : ''">
                            <td class="p-3">
                                <div class="font-bold font-mono text-blue-700" x-text="p.codigo"></div>
                                <div class="text-[10px] text-slate-400 font-mono" x-show="p.codigo_barra" x-text="p.codigo_barra"></div>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-800 leading-tight" x-text="p.descripcion"></div>
                                <div class="flex gap-1 mt-0.5">
                                    <span x-show="p.publicar_en_ecommerce==1" class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold text-[9px]"><i class="fa-solid fa-store"></i> Web</span>
                                    <span x-show="p.exento_iva==1" class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-bold text-[9px]">Exento</span>
                                </div>
                            </td>
                            <td class="p-3 text-[11px]">
                                <span class="font-semibold text-slate-700" x-text="p.departamento_nombre || '—'"></span>
                                <span x-show="p.familia_nombre" class="text-slate-400 mx-1">›</span>
                                <span x-show="p.familia_nombre" class="text-slate-600" x-text="p.familia_nombre"></span>
                                <div class="text-[10px] text-slate-400" x-text="p.categoria_nombre || ''"></div>
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700" x-text="(p.presentaciones?.length || 0) + 1 + ' SKU'"></span>
                            </td>
                            <td class="p-3 text-right font-mono text-slate-600" x-text="'$' + fmt2(p.costo_ultimo)"></td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-700" x-text="'$' + fmt2(p.precio_a)"></td>
                            <td class="p-3 text-right font-mono text-slate-500" x-text="'Bs.' + fmt0(Number(p.precio_a||0) * tasaOficial)"></td>
                            <td class="p-3 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold"
                                      :class="Number(p.stock_total||0) > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                      x-text="fmt2(p.stock_total)"></span>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex justify-center gap-1 flex-wrap">
                                    <span x-show="p.maneja_lotes==1" class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-bold text-[9px]">LOTE</span>
                                    <span x-show="p.maneja_seriales==1" class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 font-bold text-[9px]">IMEI</span>
                                </div>
                            </td>
                            <td class="p-3 text-center">
                                <button @click="editarProducto(p)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Editar ficha maestra">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="productos.length === 0 && !cargando">
                        <td colspan="10" class="p-12 text-center text-slate-400">
                            <i class="fa-solid fa-boxes-stacked text-4xl text-slate-200 block mb-2"></i>
                            No se encontraron productos en el catálogo.
                        </td>
                    </tr>
                    <tr x-show="cargando">
                        <td colspan="10" class="p-8 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Cargando catálogo...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL FICHA DE PRODUCTO — 5 PESTAÑAS -->
    <div x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-5xl w-full overflow-hidden border border-slate-100 flex flex-col max-h-[96vh]" @click.away="">
            <!-- Header -->
            <div class="bg-gradient-to-r from-slate-900 to-blue-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-blue-600 flex items-center justify-center shadow-lg">
                        <i class="fa-solid fa-box text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold" x-text="form.id ? 'Ficha: ' + form.descripcion : 'Nuevo Producto Maestro'"></h2>
                        <p class="text-[10px] text-slate-400" x-text="form.codigo ? form.codigo + (form.codigo_barra ? ' · ' + form.codigo_barra : '') : 'Configure todos los datos del artículo'"></p>
                    </div>
                </div>
                <button @click="modalAbierto = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <!-- Tabs -->
            <div class="border-b border-slate-200 bg-slate-50 px-5 flex overflow-x-auto">
                <template x-for="(label, key) in tabs" :key="key">
                    <button @click="tabActiva = key" class="py-3 px-4 text-xs font-bold border-b-2 whitespace-nowrap transition flex items-center gap-1.5"
                            :class="tabActiva === key ? 'border-blue-600 text-blue-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-800'"
                            x-text="label">
                    </button>
                </template>
            </div>

            <form @submit.prevent="guardar()" class="flex-1 overflow-y-auto">

                <!-- ===== TAB 1: DATOS GENERALES ===== -->
                <div x-show="tabActiva === 'generales'" class="p-6 text-xs space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Código Único *</label>
                            <input type="text" x-model="form.codigo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-blue-700 focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Código de Barras Principal (EAN-13 / UPC)</label>
                            <input type="text" x-model="form.codigo_barra" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="Ej: 7591001234567">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block font-bold text-slate-700 mb-1">Descripción Comercial del Producto *</label>
                            <input type="text" x-model="form.descripcion" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-800 focus:outline-none focus:border-blue-500" placeholder="Nombre completo del artículo...">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block font-bold text-slate-700 mb-1">Descripción Larga / Notas Técnicas</label>
                            <textarea x-model="form.descripcion_larga" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 resize-none focus:outline-none focus:border-blue-500" placeholder="Especificaciones, características adicionales..."></textarea>
                        </div>
                    </div>

                    <!-- Clasificación en cascada 3 niveles -->
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 space-y-3">
                        <h4 class="font-bold text-blue-900 flex items-center gap-2 text-xs">
                            <i class="fa-solid fa-sitemap text-blue-600"></i> Clasificación Jerárquica (Departamento → Familia → Categoría)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Departamento</label>
                                <select x-model.number="form.departamento_id" @change="filtrarFamiliasForm()" class="w-full bg-white border border-blue-200 rounded-xl p-2.5 font-bold">
                                    <option value="">-- Seleccionar --</option>
                                    <template x-for="d in departamentos" :key="d.id">
                                        <option :value="d.id" x-text="d.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Familia</label>
                                <select x-model.number="form.familia_id" @change="filtrarCategoriasForm()" class="w-full bg-white border border-blue-200 rounded-xl p-2.5 font-bold">
                                    <option value="">-- Seleccionar --</option>
                                    <template x-for="f in familiasForm" :key="f.id">
                                        <option :value="f.id" x-text="f.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Categoría</label>
                                <select x-model.number="form.categoria_id" class="w-full bg-white border border-blue-200 rounded-xl p-2.5 font-bold">
                                    <option value="">-- Seleccionar --</option>
                                    <template x-for="c in categoriasForm" :key="c.id">
                                        <option :value="c.id" x-text="c.descripcion"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Atributos físicos -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Unidad de Medida Base</label>
                            <select x-model.number="form.unidad_medida_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="1">Unidad (UND)</option>
                                <option value="2">Kilogramos (KG)</option>
                                <option value="3">Metros (MTS)</option>
                                <option value="4">Litros (LTS)</option>
                                <option value="5">Gramos (GR)</option>
                                <option value="6">Par (PAR)</option>
                                <option value="7">Docena (DOC)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Alícuota IVA</label>
                            <select x-model="form.porcentaje_iva" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold">
                                <option value="16.00">16.00% (General)</option>
                                <option value="8.00">8.00% (Reducido Alimentos)</option>
                                <option value="0.00">0.00% (Exento)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Marca / Fabricante</label>
                            <input type="text" x-model="form.marca" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-blue-500" placeholder="Ej: Samsung, Nestlé">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Modelo / Referencia</label>
                            <input type="text" x-model="form.modelo" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Referencia del Proveedor</label>
                            <input type="text" x-model="form.referencia_proveedor" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estado</label>
                            <select x-model.number="form.estado" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo / Sin publicar</option>
                            </select>
                        </div>
                    </div>

                    <!-- Switches de configuración -->
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
                        <h4 class="font-bold text-slate-700 text-xs">Configuración de Trazabilidad, Fiscalidad y Canales</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="flex items-center gap-2 cursor-pointer bg-white p-2.5 rounded-xl border border-slate-200 hover:border-amber-300 transition">
                                <div class="relative flex-shrink-0">
                                    <input type="checkbox" x-model="form.maneja_lotes" class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-300 peer-checked:bg-amber-500 rounded-full transition"></div>
                                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full peer-checked:translate-x-4 transition shadow"></div>
                                </div>
                                <span class="font-bold text-slate-700 text-[11px]">Lotes / FEFO</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer bg-white p-2.5 rounded-xl border border-slate-200 hover:border-purple-300 transition">
                                <div class="relative flex-shrink-0">
                                    <input type="checkbox" x-model="form.maneja_seriales" class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-300 peer-checked:bg-purple-500 rounded-full transition"></div>
                                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full peer-checked:translate-x-4 transition shadow"></div>
                                </div>
                                <span class="font-bold text-slate-700 text-[11px]">Seriales / IMEI</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer bg-white p-2.5 rounded-xl border border-slate-200 hover:border-slate-400 transition">
                                <div class="relative flex-shrink-0">
                                    <input type="checkbox" x-model="form.exento_iva" class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-300 peer-checked:bg-slate-600 rounded-full transition"></div>
                                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full peer-checked:translate-x-4 transition shadow"></div>
                                </div>
                                <span class="font-bold text-slate-700 text-[11px]">Exento IVA</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer bg-white p-2.5 rounded-xl border border-slate-200 hover:border-emerald-300 transition">
                                <div class="relative flex-shrink-0">
                                    <input type="checkbox" x-model="form.publicar_en_ecommerce" class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-300 peer-checked:bg-emerald-500 rounded-full transition"></div>
                                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full peer-checked:translate-x-4 transition shadow"></div>
                                </div>
                                <span class="font-bold text-slate-700 text-[11px]">Publicar E-commerce</span>
                            </label>
                        </div>

                        <!-- Campos condicionales E-commerce -->
                        <div x-show="form.publicar_en_ecommerce" class="pt-3 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Descripción para la Tienda Web</label>
                                <textarea x-model="form.descripcion_ecommerce" rows="2" class="w-full bg-white border border-emerald-200 rounded-xl p-2.5 resize-none focus:outline-none focus:border-emerald-500" placeholder="Texto que verá el cliente en la tienda online..."></textarea>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Palabras Clave SEO (tags)</label>
                                <input type="text" x-model="form.tags_ecommerce" class="w-full bg-white border border-emerald-200 rounded-xl p-2.5 focus:outline-none focus:border-emerald-500" placeholder="Ej: laptop, notebook, gaming">
                                <label class="block font-bold text-slate-700 mb-1 mt-2">Stock Máximo a Mostrar Online</label>
                                <input type="number" x-model.number="form.stock_maximo_ecommerce" class="w-full bg-white border border-emerald-200 rounded-xl p-2.5 font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== TAB 2: MÚLTIPLES PRESENTACIONES ===== -->
                <div x-show="tabActiva === 'presentaciones'" class="p-6 text-xs space-y-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-bold text-slate-800">Presentaciones Adicionales del Producto</h4>
                            <p class="text-slate-500 mt-0.5">La unidad base ya existe. Agregue Cajas, Bultos, Displays con su propio código de barra y factor.</p>
                        </div>
                        <button type="button" @click="agregarPresentacion()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl transition flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Agregar Presentación
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="p-2.5">Presentación</th>
                                    <th class="p-2.5">Cód. Barra Propio</th>
                                    <th class="p-2.5 text-center">Factor</th>
                                    <th class="p-2.5">Tipo Cálculo</th>
                                    <th class="p-2.5 text-right">Precio A</th>
                                    <th class="p-2.5 text-right">Precio B</th>
                                    <th class="p-2.5 text-right">Precio C</th>
                                    <th class="p-2.5 text-right">Precio D</th>
                                    <th class="p-2.5 text-center">⚙</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(pres, idx) in form.presentaciones" :key="idx">
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-2">
                                            <input type="text" x-model="pres.nombre_presentacion" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1.5 font-bold focus:outline-none focus:border-blue-500" placeholder="Caja x 24">
                                        </td>
                                        <td class="p-2">
                                            <input type="text" x-model="pres.codigo_barra" class="w-28 bg-slate-50 border border-slate-200 rounded-lg p-1.5 font-mono focus:outline-none focus:border-blue-500" placeholder="EAN">
                                        </td>
                                        <td class="p-2 text-center">
                                            <input type="number" step="1" min="1" x-model.number="pres.factor_conversion" @input="recalcPres(pres)" class="w-16 bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-center font-mono font-bold focus:outline-none focus:border-blue-500">
                                        </td>
                                        <td class="p-2">
                                            <select x-model="pres.tipo_calculo_precio" @change="recalcPres(pres)" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1.5 font-bold">
                                                <option value="PROPORCIONAL_DIRECTO">Proporcional</option>
                                                <option value="PRECIO_FIJO">Precio Fijo</option>
                                                <option value="RECARGO_FRACCION">Recargo %</option>
                                            </select>
                                        </td>
                                        <td class="p-2 text-right">
                                            <template x-if="pres.tipo_calculo_precio === 'PRECIO_FIJO'">
                                                <input type="number" step="0.01" x-model.number="pres.precio_a_fijo" class="w-20 bg-blue-50 border border-blue-200 rounded-lg p-1.5 text-right font-mono font-black text-blue-700">
                                            </template>
                                            <template x-if="pres.tipo_calculo_precio !== 'PRECIO_FIJO'">
                                                <span class="font-mono font-black text-blue-700" x-text="'$' + fmt2(Number(form.precio_a||0) * Number(pres.factor_conversion||1))"></span>
                                            </template>
                                        </td>
                                        <td class="p-2 text-right font-mono text-slate-600" x-text="'$' + fmt2(Number(form.precio_b||0) * Number(pres.factor_conversion||1))"></td>
                                        <td class="p-2 text-right font-mono text-slate-600" x-text="'$' + fmt2(Number(form.precio_c||0) * Number(pres.factor_conversion||1))"></td>
                                        <td class="p-2 text-right font-mono text-slate-600" x-text="'$' + fmt2(Number(form.precio_d||0) * Number(pres.factor_conversion||1))"></td>
                                        <td class="p-2 text-center">
                                            <button type="button" @click="form.presentaciones.splice(idx,1)" class="p-1 text-red-400 hover:text-red-600 transition">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="form.presentaciones.length === 0">
                                    <td colspan="9" class="p-6 text-center text-slate-400 border-2 border-dashed border-slate-200">
                                        Solo se vende en la unidad base. Agregue presentaciones de venta al por mayor.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Resumen de codigos de barra -->
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-xs" x-show="form.presentaciones.length > 0">
                        <h5 class="font-bold text-slate-700 mb-2">Resumen de Códigos de Barra Registrados</h5>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-blue-50 border border-blue-200 rounded-lg font-mono text-blue-700 font-bold" x-text="'UND: ' + (form.codigo_barra || 'Sin código')"></span>
                            <template x-for="pres in form.presentaciones.filter(p => p.codigo_barra)" :key="pres.codigo_barra">
                                <span class="px-3 py-1 bg-slate-100 border border-slate-300 rounded-lg font-mono text-slate-700 font-bold" x-text="pres.nombre_presentacion + ': ' + pres.codigo_barra"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ===== TAB 3: COSTOS Y PRECIOS ===== -->
                <div x-show="tabActiva === 'costos'" class="p-6 text-xs space-y-5">
                    <!-- Costos -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Costo Último / Reposición (USD) *</label>
                            <input type="number" step="0.000001" x-model.number="form.costo_ultimo" @input="calcularPrecios()" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-black text-slate-900 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Costo Promedio Kardex</label>
                            <div class="bg-slate-100 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-slate-500" x-text="'$' + fmt4(form.costo_promedio)"></div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tasa BCV Activa (Bs./USD)</label>
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-2.5 font-mono font-bold text-blue-700 text-center" x-text="'Bs. ' + fmt2(tasaOficial)"></div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Costo en Bolívares</label>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-slate-600" x-text="'Bs. ' + fmt0(Number(form.costo_ultimo||0) * tasaOficial)"></div>
                        </div>
                    </div>

                    <!-- Matriz de precios -->
                    <div class="bg-gradient-to-br from-slate-50 to-blue-50 border border-slate-200 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-tags text-blue-600"></i> Matriz de 4 Listas de Precios — Bimonetario
                            </h3>
                            <span class="text-[10px] text-slate-500">Ajuste el margen (%) y el precio USD se calcula automáticamente</span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <template x-for="lista in ['a','b','c','d']" :key="lista">
                                <div class="bg-white rounded-2xl border p-3 space-y-2 shadow-sm"
                                     :class="lista==='a' ? 'border-blue-300 ring-1 ring-blue-200' : 'border-slate-200'">
                                    <div class="text-center">
                                        <span class="text-[10px] font-black uppercase tracking-wider"
                                              :class="lista==='a' ? 'text-blue-700' : 'text-slate-600'"
                                              x-text="lista==='a' ? 'Lista A — PVP' : lista==='b' ? 'Lista B — Mayor' : lista==='c' ? 'Lista C — Distrib.' : 'Lista D — Especial'"></span>
                                    </div>
                                    <!-- Margen % -->
                                    <div class="flex items-center gap-1">
                                        <input type="number" step="0.1" min="0"
                                               :x-model="'form.utilidad_' + lista"
                                               @input="calcularPrecios()"
                                               class="w-14 bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-center font-mono text-xs focus:outline-none focus:border-blue-500">
                                        <span class="text-[10px] text-slate-500 font-bold">%</span>
                                    </div>
                                    <!-- Precio USD -->
                                    <input type="number" step="0.01" min="0"
                                           :x-model="'form.precio_' + lista"
                                           @input="calcularMargenDesdePrecio(lista)"
                                           class="w-full bg-blue-50 border border-blue-200 rounded-xl p-2 text-center font-mono font-black text-blue-900 text-sm focus:outline-none focus:border-blue-500">
                                    <!-- Precio VES -->
                                    <div class="text-center text-[10px] font-mono font-bold text-slate-400"
                                         x-text="'Bs. ' + fmt0(Number((lista==='a'?form.precio_a:lista==='b'?form.precio_b:lista==='c'?form.precio_c:form.precio_d)||0) * tasaOficial)">
                                    </div>
                                    <!-- Margen real -->
                                    <div class="text-center text-[10px] font-bold"
                                         :class="calcMargenReal(lista) >= 0 ? 'text-emerald-600' : 'text-red-500'"
                                         x-text="'Margen real: ' + calcMargenReal(lista).toFixed(1) + '%'"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ===== TAB 4: EXISTENCIAS Y DEPÓSITOS ===== -->
                <div x-show="tabActiva === 'existencias'" class="p-6 text-xs space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-warehouse text-blue-600"></i> Desglose de Inventario por Almacén / Depósito
                        </h4>
                        <div class="flex gap-2">
                            <button type="button" @click="recargarStock()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-1.5 rounded-lg border border-slate-200 transition text-xs">
                                <i class="fa-solid fa-rotate mr-1"></i>Actualizar
                            </button>
                            <button type="button" @click="modalAjuste = true" x-show="form.id" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-1.5 rounded-lg transition text-xs">
                                <i class="fa-solid fa-sliders mr-1"></i>Ajuste de Inventario
                            </button>
                        </div>
                    </div>

                    <!-- KPIs de stock -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 text-center">
                            <span class="text-[10px] font-bold text-emerald-600 uppercase block">Stock Físico Total</span>
                            <span class="text-xl font-black font-mono text-emerald-900" x-text="fmt2(form.deposito_stock.reduce((a,d)=>a+Number(d.existencia||0),0))"></span>
                        </div>
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3 text-center">
                            <span class="text-[10px] font-bold text-amber-600 uppercase block">Comprometido (Pedidos)</span>
                            <span class="text-xl font-black font-mono text-amber-900" x-text="fmt2(form.deposito_stock.reduce((a,d)=>a+Number(d.comprometida||0),0))"></span>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-3 text-center">
                            <span class="text-[10px] font-bold text-blue-600 uppercase block">Disponible Neto</span>
                            <span class="text-xl font-black font-mono text-blue-900"
                                  x-text="fmt2(form.deposito_stock.reduce((a,d)=>a+Number(d.existencia||0)-Number(d.comprometida||0),0))"></span>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-2xl overflow-hidden">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="p-3">Depósito / Almacén</th>
                                    <th class="p-3 text-center">Existencia Física</th>
                                    <th class="p-3 text-center">Comprometida</th>
                                    <th class="p-3 text-center">Disponible</th>
                                    <th class="p-3 text-center">Stock Mínimo</th>
                                    <th class="p-3 text-center">Stock Máximo</th>
                                    <th class="p-3 text-center">Semáforo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="ds in form.deposito_stock" :key="ds.deposito_id">
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-3 font-bold text-slate-800" x-text="ds.deposito_nombre || 'Depósito #' + ds.deposito_id"></td>
                                        <td class="p-3 text-center font-mono font-bold text-slate-700" x-text="fmt2(ds.existencia)"></td>
                                        <td class="p-3 text-center font-mono font-bold text-amber-600" x-text="fmt2(ds.comprometida)"></td>
                                        <td class="p-3 text-center font-mono font-bold text-emerald-700" x-text="fmt2(Number(ds.existencia||0)-Number(ds.comprometida||0))"></td>
                                        <td class="p-3 text-center">
                                            <input type="number" step="0.01" x-model.number="ds.stock_minimo" class="w-20 bg-slate-50 border border-slate-200 rounded-lg p-1 text-center font-mono focus:outline-none focus:border-blue-500">
                                        </td>
                                        <td class="p-3 text-center">
                                            <input type="number" step="0.01" x-model.number="ds.stock_maximo" class="w-20 bg-slate-50 border border-slate-200 rounded-lg p-1 text-center font-mono focus:outline-none focus:border-blue-500">
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="w-5 h-5 inline-flex items-center justify-center rounded-full text-[10px] font-black"
                                                  :class="Number(ds.existencia||0) <= 0 ? 'bg-red-500 text-white' : Number(ds.existencia||0) <= Number(ds.stock_minimo||0) ? 'bg-amber-400 text-white' : 'bg-emerald-500 text-white'">
                                                <i :class="Number(ds.existencia||0) <= 0 ? 'fa-solid fa-xmark' : Number(ds.existencia||0) <= Number(ds.stock_minimo||0) ? 'fa-solid fa-exclamation' : 'fa-solid fa-check'"></i>
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!form.deposito_stock || form.deposito_stock.length === 0">
                                    <td colspan="7" class="p-6 text-center text-slate-400">
                                        <i class="fa-solid fa-warehouse text-2xl text-slate-200 block mb-2"></i>
                                        Stock almacenado en Depósito Principal. Guarde primero para ver el desglose.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===== TAB 5: TRAZABILIDAD ===== -->
                <div x-show="tabActiva === 'trazabilidad'" class="p-6 text-xs space-y-5">
                    <!-- Seriales / IMEI -->
                    <div x-show="form.maneja_seriales" class="space-y-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-bold text-purple-900 flex items-center gap-2">
                                <i class="fa-solid fa-microchip text-purple-600"></i> Control de Seriales / IMEI
                            </h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Días de Garantía Legal</label>
                                <input type="number" min="0" x-model.number="form.dias_garantia" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold" placeholder="Ej: 365">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tipo de Serial</label>
                                <select x-model="form.tipo_serial" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold">
                                    <option value="IMEI">IMEI (15 dígitos)</option>
                                    <option value="SN">Número de Serie General</option>
                                    <option value="MAC">Dirección MAC</option>
                                    <option value="LIBRE">Serial Libre</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <div class="w-full bg-purple-50 border border-purple-200 rounded-xl p-3 text-center">
                                    <span class="text-[10px] font-bold text-purple-600 uppercase block">Seriales Disponibles</span>
                                    <span class="text-2xl font-black font-mono text-purple-900" x-text="form.seriales?.length || 0"></span>
                                </div>
                            </div>
                        </div>
                        <!-- Tabla de seriales disponibles -->
                        <div x-show="form.seriales && form.seriales.length > 0" class="border border-purple-100 rounded-2xl overflow-hidden max-h-64 overflow-y-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-purple-50 text-purple-700 font-bold uppercase text-[10px] sticky top-0">
                                    <tr><th class="p-2">Serial / IMEI</th><th class="p-2">Estado</th><th class="p-2">Depósito</th><th class="p-2">F. Ingreso</th></tr>
                                </thead>
                                <tbody class="divide-y divide-purple-50">
                                    <template x-for="s in form.seriales" :key="s.serial">
                                        <tr>
                                            <td class="p-2 font-mono font-bold text-purple-800" x-text="s.serial"></td>
                                            <td class="p-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="s.estado==='DISPONIBLE'?'bg-emerald-100 text-emerald-700':'bg-amber-100 text-amber-700'" x-text="s.estado"></span></td>
                                            <td class="p-2 text-slate-600" x-text="s.deposito_nombre || '—'"></td>
                                            <td class="p-2 font-mono text-slate-500" x-text="s.fecha_ingreso || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Lotes FEFO -->
                    <div x-show="form.maneja_lotes" class="space-y-3">
                        <h4 class="font-bold text-amber-900 flex items-center gap-2">
                            <i class="fa-solid fa-boxes-packing text-amber-600"></i> Trazabilidad FEFO — Lotes Activos
                        </h4>
                        <div x-show="form.lotes && form.lotes.length > 0" class="border border-amber-100 rounded-2xl overflow-hidden">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-amber-50 text-amber-700 font-bold uppercase text-[10px]">
                                    <tr><th class="p-2.5">Nº Lote</th><th class="p-2.5">Fabricación</th><th class="p-2.5">Vencimiento</th><th class="p-2.5 text-center">Existencia</th><th class="p-2.5 text-center">Semáforo</th></tr>
                                </thead>
                                <tbody class="divide-y divide-amber-50">
                                    <template x-for="lote in form.lotes" :key="lote.id">
                                        <tr class="hover:bg-amber-50/40">
                                            <td class="p-2.5 font-mono font-bold text-amber-800" x-text="lote.numero_lote"></td>
                                            <td class="p-2.5 font-mono text-slate-600" x-text="lote.fecha_elaboracion || '—'"></td>
                                            <td class="p-2.5 font-mono font-bold"
                                                :class="diasParaVencer(lote.fecha_vencimiento) <= 30 ? 'text-red-600' : diasParaVencer(lote.fecha_vencimiento) <= 60 ? 'text-amber-600' : 'text-emerald-600'"
                                                x-text="lote.fecha_vencimiento"></td>
                                            <td class="p-2.5 text-center font-mono font-bold text-slate-700" x-text="fmt2(lote.existencia)"></td>
                                            <td class="p-2.5 text-center">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                      :class="diasParaVencer(lote.fecha_vencimiento) <= 0 ? 'bg-red-100 text-red-700' : diasParaVencer(lote.fecha_vencimiento) <= 30 ? 'bg-red-50 text-red-600' : diasParaVencer(lote.fecha_vencimiento) <= 60 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                                                      x-text="diasParaVencer(lote.fecha_vencimiento) <= 0 ? '⛔ Vencido' : diasParaVencer(lote.fecha_vencimiento) + 'd'">
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div x-show="!form.lotes || form.lotes.length === 0" class="text-center py-6 text-slate-400 border-2 border-dashed border-amber-200 rounded-2xl">
                            <i class="fa-solid fa-boxes-packing text-2xl block mb-2 text-amber-200"></i>
                            No hay lotes activos. Se generan automáticamente al registrar recepciones de compra.
                        </div>
                    </div>

                    <!-- Aviso si ninguna opción activa -->
                    <div x-show="!form.maneja_seriales && !form.maneja_lotes" class="text-center py-10 text-slate-400">
                        <i class="fa-solid fa-shield-halved text-4xl block mb-3 text-slate-200"></i>
                        <p class="font-semibold">Este producto no tiene trazabilidad activa.</p>
                        <p class="text-xs mt-1">Active "Lotes/FEFO" o "Seriales/IMEI" en la pestaña <strong>Datos Generales</strong>.</p>
                    </div>
                </div>

                <!-- Botones pie de form -->
                <div class="p-5 border-t border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div class="text-[10px] text-slate-400 font-mono" x-show="form.id">
                        ID: <span x-text="form.id"></span> · Última mod.: <span x-text="form.updated_at || '—'"></span>
                    </div>
                    <div class="flex gap-2 ml-auto">
                        <button type="button" @click="modalAbierto = false" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition">Cancelar</button>
                        <button type="submit" :disabled="guardando" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-extrabold px-8 py-2.5 rounded-xl transition shadow flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk" x-show="!guardando"></i>
                            <i class="fa-solid fa-spinner fa-spin" x-show="guardando"></i>
                            <span x-text="guardando ? 'Guardando...' : 'Guardar Ficha Maestra'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function maestroProductosApp() {
    return {
        productos: [],
        departamentos: [],
        familiasAll: [],
        categoriasAll: [],
        familiasForm: [],
        categoriasForm: [],
        tasaOficial: 1.0,
        cargando: false,
        guardando: false,
        modalAbierto: false,
        modalAjuste: false,
        tabActiva: 'generales',
        filtroBusqueda: '',
        filtroDepto: '',
        tabs: {
            generales:      '① Datos Generales',
            presentaciones: '② Multi-Presentaciones',
            costos:         '③ Costos & Precios',
            existencias:    '④ Depósitos & Stock',
            trazabilidad:   '⑤ Trazabilidad'
        },
        form: {
            id: null, codigo: '', codigo_barra: '', descripcion: '',
            descripcion_larga: '', descripcion_ecommerce: '', tags_ecommerce: '',
            departamento_id: '', familia_id: '', categoria_id: '',
            unidad_medida_id: 1, porcentaje_iva: '16.00', marca: '', modelo: '',
            referencia_proveedor: '', tipo_serial: 'SN',
            exento_iva: false, maneja_lotes: false, maneja_seriales: false,
            publicar_en_ecommerce: false, stock_maximo_ecommerce: 999,
            dias_garantia: 0, estado: 1,
            costo_ultimo: 0, costo_promedio: 0,
            utilidad_a: 30, precio_a: 0,
            utilidad_b: 25, precio_b: 0,
            utilidad_c: 20, precio_c: 0,
            utilidad_d: 15, precio_d: 0,
            stock_minimo: 0,
            presentaciones: [], deposito_stock: [], lotes: [], seriales: [],
            updated_at: null
        },

        async init() {
            await Promise.all([this.cargarTasaBCV(), this.cargarCatalogos()]);
            await this.cargarProductos();
        },

        async cargarTasaBCV() {
            try {
                const r = await fetch('/api/monedas/tasas');
                const j = await r.json();
                const usd = (j.data || []).find(t => t.codigo === 'USD');
                if (usd) this.tasaOficial = Number(usd.tasa_oficial);
            } catch(e) {}
        },

        async cargarCatalogos() {
            try {
                const r = await fetch('/api/maestros/clasificacion');
                const j = await r.json();
                this.departamentos = j.departamentos || [];
                this.familiasAll = j.familias || [];
                this.categoriasAll = j.categorias || [];
            } catch(e) {
                // fallback: cargar desde el endpoint legado
                try {
                    const r2 = await fetch('/api/maestros/departamentos');
                    const j2 = await r2.json();
                    this.departamentos = j2.departamentos || j2.data || [];
                    this.categoriasAll = j2.categorias || [];
                } catch(e2) {}
            }
        },

        filtrarFamilias() {
            this.familiasForm = this.filtroDepto
                ? this.familiasAll.filter(f => f.departamento_id == this.filtroDepto)
                : this.familiasAll;
        },

        filtrarFamiliasForm() {
            this.familiasForm = this.form.departamento_id
                ? this.familiasAll.filter(f => f.departamento_id == this.form.departamento_id)
                : this.familiasAll;
            this.form.familia_id = '';
            this.form.categoria_id = '';
            this.categoriasForm = [];
        },

        filtrarCategoriasForm() {
            this.categoriasForm = this.form.familia_id
                ? this.categoriasAll.filter(c => c.familia_id == this.form.familia_id)
                : this.categoriasAll;
            this.form.categoria_id = '';
        },

        async cargarProductos() {
            this.cargando = true;
            try {
                const url = `/api/maestros/productos?q=${encodeURIComponent(this.filtroBusqueda)}&depto_id=${encodeURIComponent(this.filtroDepto)}`;
                const j = await (await fetch(url)).json();
                this.productos = j.data || [];
            } catch(e) { this.productos = []; } finally { this.cargando = false; }
        },

        nuevoProducto() {
            this.tabActiva = 'generales';
            this.familiasForm = [];
            this.categoriasForm = [];
            this.form = {
                id: null, codigo: 'PRD-' + String(this.productos.length + 1).padStart(5,'0'),
                codigo_barra: '', descripcion: '', descripcion_larga: '',
                descripcion_ecommerce: '', tags_ecommerce: '',
                departamento_id: '', familia_id: '', categoria_id: '',
                unidad_medida_id: 1, porcentaje_iva: '16.00', marca: '', modelo: '',
                referencia_proveedor: '', tipo_serial: 'SN',
                exento_iva: false, maneja_lotes: false, maneja_seriales: false,
                publicar_en_ecommerce: false, stock_maximo_ecommerce: 999,
                dias_garantia: 0, estado: 1,
                costo_ultimo: 0, costo_promedio: 0,
                utilidad_a: 30, precio_a: 0,
                utilidad_b: 25, precio_b: 0,
                utilidad_c: 20, precio_c: 0,
                utilidad_d: 15, precio_d: 0,
                stock_minimo: 0,
                presentaciones: [], deposito_stock: [], lotes: [], seriales: [],
                updated_at: null
            };
            this.modalAbierto = true;
        },

        editarProducto(p) {
            this.tabActiva = 'generales';
            this.form = { ...JSON.parse(JSON.stringify(p)),
                presentaciones: p.presentaciones || [],
                deposito_stock: p.deposito_stock || [],
                lotes: p.lotes || [],
                seriales: p.seriales || []
            };
            // Reconstruir cascada
            if (this.form.departamento_id) {
                this.familiasForm = this.familiasAll.filter(f => f.departamento_id == this.form.departamento_id);
            }
            if (this.form.familia_id) {
                this.categoriasForm = this.categoriasAll.filter(c => c.familia_id == this.form.familia_id);
            }
            this.modalAbierto = true;
        },

        agregarPresentacion() {
            this.form.presentaciones.push({
                nombre_presentacion: 'Caja x 12',
                codigo_barra: '',
                factor_conversion: 12,
                tipo_calculo_precio: 'PROPORCIONAL_DIRECTO',
                porcentaje_recargo_fraccion: 0,
                precio_a_fijo: 0
            });
        },

        recalcPres(pres) {
            // No se necesita cálculo local ya que las columnas se calculan reactivamente en el template
        },

        calcularPrecios() {
            const c = Number(this.form.costo_ultimo || 0);
            this.form.precio_a = parseFloat((c * (1 + Number(this.form.utilidad_a||0) / 100)).toFixed(4));
            this.form.precio_b = parseFloat((c * (1 + Number(this.form.utilidad_b||0) / 100)).toFixed(4));
            this.form.precio_c = parseFloat((c * (1 + Number(this.form.utilidad_c||0) / 100)).toFixed(4));
            this.form.precio_d = parseFloat((c * (1 + Number(this.form.utilidad_d||0) / 100)).toFixed(4));
        },

        calcularMargenDesdePrecio(lista) {
            const c = Number(this.form.costo_ultimo || 0);
            if (c <= 0) return;
            const precio = Number(this.form['precio_' + lista] || 0);
            if (precio > 0) {
                this.form['utilidad_' + lista] = parseFloat(((precio / c - 1) * 100).toFixed(2));
            }
        },

        calcMargenReal(lista) {
            const c = Number(this.form.costo_ultimo || 0);
            const p = Number(this.form['precio_' + lista] || 0);
            if (c <= 0) return 0;
            return (p / c - 1) * 100;
        },

        async recargarStock() {
            if (!this.form.id) return;
            try {
                const r = await fetch(`/api/maestros/productos/${this.form.id}/stock`);
                const j = await r.json();
                if (j.data) this.form.deposito_stock = j.data;
            } catch(e) {}
        },

        diasParaVencer(fecha) {
            if (!fecha) return 9999;
            const hoy = new Date();
            const ven = new Date(fecha);
            return Math.floor((ven - hoy) / 86400000);
        },

        async guardar() {
            this.guardando = true;
            try {
                const res = await fetch('/api/maestros/productos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.modalAbierto = false;
                    await this.cargarProductos();
                } else {
                    alert('❌ Error: ' + (json.mensaje || json.message));
                }
            } catch(e) { alert('Error: ' + e.message); } finally { this.guardando = false; }
        },

        fmt2(v) { return Number(v||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); },
        fmt4(v) { return Number(v||0).toLocaleString('en-US',{minimumFractionDigits:4,maximumFractionDigits:4}); },
        fmt0(v) { return Number(v||0).toLocaleString('es-VE',{minimumFractionDigits:0,maximumFractionDigits:0}); }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
