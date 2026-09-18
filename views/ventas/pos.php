<?php
$pageTitle = 'Punto de Venta / Facturación - mi ERP';
$activeMenu = 'pos';
ob_start();
?>
<div class="space-y-4" x-data="posApp()" @keydown.window.f2.prevent="$refs.inputBusqueda.focus()" @keydown.window.f9.prevent="abrirModalCobro()">
    <!-- Barra Superior POS -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800">Terminal POS / Facturación Bimonetaria</h2>
                <p class="text-xs text-slate-500 font-medium">Facturación asistida por teclado (F2: Buscar, F9: Cobrar) con control de seriales y lotes</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="abrirPanelPedidosWeb()" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-2 border border-indigo-200">
                <i class="fa-solid fa-globe"></i> Pedidos Web (<span x-text="pedidosWebCount"></span>)
                <i class="fa-solid fa-chevron-down text-[10px] transition" :class="panelPedidosWebAbierto ? 'rotate-180' : ''"></i>
            </button>
            <button @click="imprimirCierreZ()" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Reporte Z / Arqueo
            </button>
        </div>
    </div>

    <!-- Pedidos Web Validados: Panel desplegable (facturación de pedidos online) -->
    <div x-show="panelPedidosWebAbierto" x-cloak class="bg-white rounded-2xl shadow-sm border border-emerald-200 overflow-hidden">
        <?php require __DIR__ . '/../pos/pedidos_web_confirmados.php'; ?>
    </div>

    <!-- Grid Principal: Renglones + Totales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Panel Izquierdo: Formulario de Ítems y Tabla de Facturación (2 Columnas) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
            
            <!-- Cabecera del Documento -->
            <div class="p-4 border-b border-slate-100 grid grid-cols-1 sm:grid-cols-4 gap-3 bg-slate-50">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Tipo de Documento</label>
                    <select x-model="cabecera.tipo_documento" class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl p-2.5">
                        <option value="FACTURA">Factura Fiscal</option>
                        <option value="NOTA_ENTREGA">Nota de Entrega</option>
                        <option value="PRESUPUESTO">Presupuesto</option>
                        <option value="PEDIDO">Pedido / Apartado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Cliente</label>
                    <select x-model.number="cabecera.cliente_id" class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl p-2.5">
                        <option value="1">Cliente Contado / Casual (J-00000000-0)</option>
                        <template x-for="c in clientes" :key="c.id">
                            <option :value="c.id" x-text="c.razon_social + ' (' + c.documento_fiscal + ')'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Condición</label>
                    <select x-model="cabecera.condicion_pago" class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl p-2.5">
                        <option value="CONTADO">Contado</option>
                        <option value="CREDITO">Crédito</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Depósito Origen</label>
                    <select x-model.number="cabecera.deposito_id" class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl p-2.5">
                        <option value="1">Depósito Principal (Almacén 1)</option>
                    </select>
                </div>
            </div>

            <!-- Buscador Rápido con Autocompletado Inteligente -->
            <div class="p-4 border-b border-slate-100">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-barcode text-sm"></i>
                        </span>
                        <input type="text" x-ref="inputBusqueda" x-model="busqueda"
                               @keydown.enter.prevent="seleccionarPrimerResultado()"
                               @input.debounce.200ms="buscarProductos()"
                               @keydown.escape="resultadosBusqueda = []"
                               @keydown.arrow-down.prevent="navegarResultados(1)"
                               @keydown.arrow-up.prevent="navegarResultados(-1)"
                               @focus="if(busqueda.length >= 1) buscarProductos()"
                               @blur.debounce.150ms="resultadosBusqueda = []"
                               placeholder="Escanear código de barra o buscar por nombre (F2)..." 
                               class="w-full pl-9 pr-3 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 font-mono">
                        <!-- Dropdown de Resultados -->
                        <div x-show="resultadosBusqueda.length > 0" class="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-72 overflow-y-auto">
                            <template x-for="(prod, idx) in resultadosBusqueda" :key="prod.id">
                                <div @mousedown.prevent="agregarProducto(prod)"
                                     :class="idx === resultadoIndex ? 'bg-blue-50 border-l-2 border-blue-500' : 'hover:bg-slate-50'"
                                     class="p-3 cursor-pointer border-b border-slate-100 last:border-0 flex justify-between items-center transition">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-xs text-slate-800 truncate" x-text="prod.descripcion"></div>
                                        <div class="text-[10px] text-slate-500 font-mono" x-text="prod.codigo + (prod.codigo_barras ? ' | ' + prod.codigo_barras : '')"></div>
                                    </div>
                                    <div class="ml-3 text-right flex-shrink-0">
                                        <div class="font-mono font-bold text-xs text-emerald-700" x-text="'$' + formatMoney(prod.precio_a || 0)"></div>
                                        <div class="text-[10px]">
                                            <span :class="prod.existencia_total > 0 ? 'text-emerald-600' : 'text-red-500'" 
                                                  x-text="prod.existencia_total > 0 ? 'Stock: ' + prod.existencia_total : 'SIN STOCK'" class="font-bold"></span>
                                            <span x-show="prod.maneja_seriales == 1" class="ml-1 text-[9px] bg-purple-100 text-purple-700 px-1 rounded font-bold">IMEI</span>
                                            <span x-show="prod.maneja_lotes == 1" class="ml-1 text-[9px] bg-amber-100 text-amber-700 px-1 rounded font-bold">LOTE</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <button @click="seleccionarPrimerResultado()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Agregar
                    </button>
                </div>
            </div>

            <!-- Tabla de Renglones -->
            <div class="flex-1 overflow-y-auto p-4 min-h-[300px]">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 uppercase text-[11px] font-bold">
                            <th class="py-2.5">Código / Barra</th>
                            <th class="py-2.5">Descripción</th>
                            <th class="py-2.5">Presentación SKU</th>
                            <th class="py-2.5 text-center w-20">Cant.</th>
                            <th class="py-2.5 text-right">P. Unit ($)</th>
                            <th class="py-2.5 text-right">Subtotal ($)</th>
                            <th class="py-2.5 text-center w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-2.5 font-bold text-blue-700" x-text="item.codigo"></td>
                                <td class="py-2.5 font-sans font-medium text-slate-900">
                                    <div x-text="item.descripcion"></div>
                                    <div class="text-[10px] text-purple-600 font-bold" x-show="item.serial_seleccionado" x-text="'IMEI: ' + item.serial_seleccionado"></div>
                                    <div class="text-[10px] text-amber-600 font-bold" x-show="item.lote_seleccionado" x-text="'Lote: ' + item.lote_seleccionado"></div>
                                </td>
                                <td class="py-2.5">
                                    <select x-model="item.presentacion_id" @change="cambiarPresentacionItem(item)" class="bg-slate-50 border border-slate-200 rounded p-1 text-[11px] font-sans font-bold">
                                        <option value="base">Unidad Base (x1)</option>
                                        <template x-for="pres in item.presentaciones_disponibles" :key="pres.id">
                                            <option :value="pres.id" x-text="pres.nombre_presentacion + ' (x' + pres.factor_conversion + ')'"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="py-2.5 text-center">
                                    <input type="number" min="1" x-model.number="item.cantidad" class="w-16 border border-slate-200 rounded-lg p-1 text-center font-bold font-mono">
                                </td>
                                <td class="py-2.5 text-right" x-text="'$' + formatMoney(item.precio_unitario)"></td>
                                <td class="py-2.5 text-right font-bold text-slate-900" x-text="'$' + formatMoney(item.cantidad * item.precio_unitario)"></td>
                                <td class="py-2.5 text-center">
                                    <button @click="eliminarItem(index)" class="text-red-400 hover:text-red-600 p-1">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <td colspan="7" class="text-center py-16 text-slate-400 font-sans">
                                <i class="fa-solid fa-cart-shopping text-3xl mb-2 text-slate-300 block"></i>
                                No hay productos en el carrito. Escanee un código o busque un producto para comenzar.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel Derecho: Totales y Liquidación (1 Columna) -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between space-y-6">
            <div>
                <h3 class="font-bold text-slate-800 text-sm border-b pb-3 mb-4 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-blue-600"></i> Resumen de Venta
                </h3>
                
                <div class="space-y-3 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal Neto:</span>
                        <span class="font-mono font-bold text-slate-800" x-text="'$' + formatMoney(totales.subtotal)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Monto Exento:</span>
                        <span class="font-mono font-medium" x-text="'$' + formatMoney(totales.exento)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Base Imponible:</span>
                        <span class="font-mono font-medium" x-text="'$' + formatMoney(totales.baseImponible)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>IVA (16%):</span>
                        <span class="font-mono font-bold text-red-500" x-text="'$' + formatMoney(totales.iva)"></span>
                    </div>
                    <div class="flex justify-between text-orange-600" x-show="totales.igtf > 0">
                        <span class="font-bold">IGTF Divisas (3%):</span>
                        <span class="font-mono font-bold" x-text="'$' + formatMoney(totales.igtf)"></span>
                    </div>
                </div>

                <!-- Total Destacado Bimonetario -->
                <div class="mt-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-5 text-center shadow-inner">
                    <p class="text-[11px] uppercase font-bold text-blue-600 tracking-wider">Total a Pagar</p>
                    <p class="text-3xl font-black text-blue-900 font-mono mt-1" x-text="'$' + formatMoney(totales.total)"></p>
                    <p class="text-xs font-bold text-slate-600 mt-2 font-mono bg-white/70 py-1 px-3 rounded-full border border-blue-100 inline-block" x-text="'Equivalente: Bs. ' + formatMoney(totales.total * tasa)"></p>
                </div>
            </div>

            <!-- Botón de Cobro Multimoneda (F9) -->
            <button @click="abrirModalCobro()" :disabled="items.length === 0 || procesando" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-sm py-4 rounded-xl shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-circle-dollar-to-slot"></i>
                <span>Cobrar / Formas de Pago (F9)</span>
            </button>
        </div>
    </div>

    <!-- Modal Flotante de Cobro Multimoneda (F9) -->
    <div x-show="modalCobroAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-100 flex flex-col" @click.away="modalCobroAbierto = false">
            <div class="bg-slate-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold">Registro de Cobro y Formas de Pago</h2>
                        <p class="text-[10px] text-slate-400 font-mono" x-text="'Monto Total: $' + formatMoney(totales.total) + ' | Bs. ' + formatMoney(totales.total * tasa)"></p>
                    </div>
                </div>
                <button @click="modalCobroAbierto = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="p-6 space-y-4 text-xs overflow-y-auto max-h-[75vh]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Efectivo Dólares ($)</label>
                        <input type="number" step="0.01" x-model.number="cobro.efectivo_usd" @input="calcularVuelto()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-emerald-700">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Efectivo Bolívares (Bs.)</label>
                        <input type="number" step="0.01" x-model.number="cobro.efectivo_ves" @input="calcularVuelto()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-blue-700">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pago Móvil C2P (Bs.)</label>
                        <input type="number" step="0.01" x-model.number="cobro.pago_movil" @input="calcularVuelto()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-slate-800">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Punto de Venta / Tarjeta ($)</label>
                        <input type="number" step="0.01" x-model.number="cobro.punto_venta" @input="calcularVuelto()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-slate-800">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Zelle ($)</label>
                        <input type="number" step="0.01" x-model.number="cobro.zelle" @input="calcularVuelto()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-indigo-700">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Transferencia ($)</label>
                        <input type="number" step="0.01" x-model.number="cobro.transferencia" @input="calcularVuelto()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold text-slate-800">
                    </div>
                </div>

                <!-- Resumen de Vuelto -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 flex justify-between items-center">
                    <div>
                        <span class="text-slate-500 font-bold block">Vuelto a Entregar:</span>
                        <span class="font-mono font-black text-xl text-emerald-600" x-text="'$' + formatMoney(cobro.vuelto_usd)"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 font-bold block">Equivalente Vuelto Bs:</span>
                        <span class="font-mono font-black text-xl text-blue-600" x-text="'Bs. ' + formatMoney(cobro.vuelto_usd * tasa)"></span>
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="modalCobroAbierto = false" class="px-4 py-2 text-slate-500 hover:text-slate-800 font-bold">Cancelar</button>
                    <button type="button" @click="procesarFactura()" :disabled="procesando" class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-6 py-2.5 rounded-xl transition shadow">
                        <span x-text="procesando ? 'Procesando Venta...' : 'Confirmar Cobro e Imprimir'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Captura de Serial / Lote Obligatorio -->
    <div x-show="modalSerialLote" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100" @click.away="">
            <div class="bg-gradient-to-r from-purple-900 to-slate-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         :class="itemSerialActual?.maneja_seriales == 1 ? 'bg-purple-600' : 'bg-amber-600'">
                        <i :class="itemSerialActual?.maneja_seriales == 1 ? 'fa-solid fa-microchip' : 'fa-solid fa-boxes-packing'"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold">Asignación de Trazabilidad Obligatoria</h2>
                        <p class="text-[10px] text-slate-400" x-text="itemSerialActual?.descripcion || ''" ></p>
                    </div>
                </div>
            </div>
            <div class="p-6 text-xs space-y-4">
                <!-- Serial/IMEI -->
                <div x-show="itemSerialActual?.maneja_seriales == 1 || itemSerialActual?.seriales?.length > 0">
                    <label class="block font-bold text-slate-700 mb-2">Seleccionar Serial / IMEI Disponible</label>
                    <select x-model="serialSeleccionado" class="w-full border border-purple-200 bg-purple-50 rounded-xl p-2.5 font-mono font-bold text-purple-700 focus:outline-none focus:border-purple-500">
                        <option value="">-- Seleccione un serial --</option>
                        <template x-for="s in (itemSerialActual?.seriales || [])" :key="s.id">
                            <option :value="s.numero_serial" x-text="s.numero_serial + (s.garantia_dias ? ' (G: ' + s.garantia_dias + 'd)' : '')"></option>
                        </template>
                    </select>
                    <div x-show="!itemSerialActual?.seriales?.length" class="mt-2">
                        <label class="block font-bold text-slate-600 mb-1">O ingresar serial manualmente:</label>
                        <input type="text" x-model="serialSeleccionado" placeholder="Ingrese serial/IMEI..." class="w-full border border-purple-200 bg-purple-50 rounded-xl p-2.5 font-mono focus:outline-none focus:border-purple-500">
                    </div>
                    <div class="mt-2 pt-2 border-t border-purple-100 flex justify-end">
                        <button type="button" @click="abrirEscaneoSeriales()"
                                class="text-[11px] font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1.5 transition">
                            <i class="fa-solid fa-barcode-scan"></i>
                            <span x-text="'Escanear con pistola los ' + itemSerialActual.cantidad + ' serial(es) del renglón'"></span>
                        </button>
                    </div>
                </div>

                <!-- Lote FEFO -->
                <div x-show="itemSerialActual?.maneja_lotes == 1 || itemSerialActual?.lotes?.length > 0">
                    <label class="block font-bold text-slate-700 mb-2">Seleccionar Lote (FEFO - Primer en Vencer)</label>
                    <div class="space-y-1.5">
                        <template x-for="l in (itemSerialActual?.lotes || [])" :key="l.id">
                            <label class="flex items-center justify-between p-2.5 border rounded-xl cursor-pointer hover:bg-amber-50 transition"
                                   :class="loteSeleccionado === l.numero_lote ? 'border-amber-500 bg-amber-50' : 'border-slate-200'">
                                <div class="flex items-center gap-2">
                                    <input type="radio" :value="l.numero_lote" x-model="loteSeleccionado" class="text-amber-600">
                                    <div>
                                        <span class="font-mono font-bold text-slate-800" x-text="l.numero_lote"></span>
                                        <span class="ml-2 text-[10px] font-bold px-1.5 py-0.5 rounded"
                                              :class="diasParaVencer(l.fecha_vencimiento) < 30 ? 'bg-red-100 text-red-700' : diasParaVencer(l.fecha_vencimiento) < 90 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                                              x-text="'Vence: ' + l.fecha_vencimiento + ' (' + diasParaVencer(l.fecha_vencimiento) + 'd)'"></span>
                                    </div>
                                </div>
                                <span class="font-mono font-bold text-slate-600" x-text="'Exist: ' + parseFloat(l.existencia || 0).toFixed(0)"></span>
                            </label>
                        </template>
                        <div x-show="!itemSerialActual?.lotes?.length">
                            <input type="text" x-model="loteSeleccionado" placeholder="Ingrese número de lote..." class="w-full border border-amber-200 bg-amber-50 rounded-xl p-2.5 font-mono focus:outline-none focus:border-amber-500">
                        </div>
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="cancelarSerialLote()" class="px-4 py-2 text-slate-500 hover:text-slate-800 font-bold">Cancelar</button>
                    <button type="button" @click="confirmarSerialLote()"
                            :disabled="(itemSerialActual?.maneja_seriales && !serialSeleccionado) && (itemSerialActual?.maneja_lotes && !loteSeleccionado)"
                            class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-extrabold px-6 py-2.5 rounded-xl transition shadow">
                        <i class="fa-solid fa-check mr-1"></i> Confirmar y Agregar al Carrito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Escaneo Masivo de Seriales (pistola de código de barras) -->
    <?php require __DIR__ . '/../pos/modal_seriales.php'; ?>
</div>

<script>
function posApp() {
    return {
        tasa: <?= \App\Core\Database::getTasaActualUsd() ?>,
        busqueda: '',
        resultadosBusqueda: [],
        resultadoIndex: -1,
        _todosProductos: [],
        procesando: false,
        modalCobroAbierto: false,
        modalSerialLote: false,
        panelPedidosWebAbierto: false,
        pedidosWebCount: 0,
        modalSerialesAbierto: false,
        productoSerialActual: null,
        serialesEscaneados: [],
        inputSerial: '',
        itemSerialActual: null,
        serialSeleccionado: '',
        loteSeleccionado: '',
        _pendingItem: null,
        clientes: [],
        pedidosWeb: [],
        cabecera: {
            tipo_documento: 'FACTURA',
            numero_documento: 'FAC-' + Math.floor(Math.random() * 90000 + 10000),
            cliente_id: 1,
            vendedor_id: 1,
            deposito_id: 1,
            condicion_pago: 'CONTADO'
        },
        items: [],
        cobro: {
            efectivo_usd: 0,
            efectivo_ves: 0,
            pago_movil: 0,
            punto_venta: 0,
            zelle: 0,
            transferencia: 0,
            vuelto_usd: 0
        },
        async init() {
            window.addEventListener('pedidos-web-cargados', (e) => { this.pedidosWebCount = e.detail; });
            await Promise.all([this.cargarClientes(), this.precargarProductos()]);
        },
        async cargarClientes() {
            try {
                const res = await fetch('/api/clientes');
                const json = await res.json();
                this.clientes = json.data || [];
            } catch(e) {}
        },
        async precargarProductos() {
            try {
                const res = await fetch('/api/maestros/productos?limit=2000');
                const json = await res.json();
                this._todosProductos = json.data || [];
            } catch(e) {}
        },
        buscarProductos() {
            const q = this.busqueda.trim().toLowerCase();
            this.resultadoIndex = -1;
            if (q.length < 1) { this.resultadosBusqueda = []; return; }
            this.resultadosBusqueda = this._todosProductos.filter(p => {
                return (p.codigo && p.codigo.toLowerCase().includes(q)) ||
                       (p.descripcion && p.descripcion.toLowerCase().includes(q)) ||
                       (p.codigo_barras && p.codigo_barras.toLowerCase().includes(q)) ||
                       (p.codigo_barra && p.codigo_barra.toLowerCase().includes(q));
            }).slice(0, 12);
        },
        navegarResultados(dir) {
            const max = this.resultadosBusqueda.length - 1;
            this.resultadoIndex = Math.max(-1, Math.min(max, this.resultadoIndex + dir));
        },
        seleccionarPrimerResultado() {
            const idx = this.resultadoIndex >= 0 ? this.resultadoIndex : 0;
            if (this.resultadosBusqueda[idx]) {
                this.agregarProducto(this.resultadosBusqueda[idx]);
            } else if (this.busqueda.trim()) {
                // Búsqueda directa por código exacto en caso de escaneo
                const exacto = this._todosProductos.find(p => 
                    p.codigo === this.busqueda.trim() || p.codigo_barras === this.busqueda.trim() || p.codigo_barra === this.busqueda.trim()
                );
                if (exacto) this.agregarProducto(exacto);
                else alert('Producto no encontrado: ' + this.busqueda);
            }
        },
        agregarProducto(p) {
            this.resultadosBusqueda = [];
            this.busqueda = '';
            const newItem = {
                producto_id: p.id,
                codigo: p.codigo,
                descripcion: p.descripcion,
                maneja_seriales: p.maneja_seriales,
                maneja_lotes: p.maneja_lotes,
                seriales: p.seriales || [],
                lotes: p.lotes || [],
                presentacion_id: 'base',
                presentaciones_disponibles: p.presentaciones || [],
                cantidad: 1,
                precio_unitario_base: Number(p.precio_a || 0),
                precio_unitario: Number(p.precio_a || 0),
                porcentaje_iva: Number(p.porcentaje_iva || 16),
                exento_iva: Number(p.exento_iva || 0),
                serial_seleccionado: null,
                seriales_asignados: null,
                lote_seleccionado: null
            };
            const existe = this.items.find(i => i.producto_id === p.id && !p.maneja_seriales);
            if (existe) {
                existe.cantidad++;
            } else if (p.maneja_seriales == 1 || p.maneja_lotes == 1) {
                this._pendingItem = newItem;
                this.itemSerialActual = newItem;
                this.serialSeleccionado = '';
                this.loteSeleccionado = '';
                this.modalSerialLote = true;
            } else {
                this.items.push(newItem);
            }
        },
        // Legacy - keep for backward compat
        async buscarYAgregar() {
            this.seleccionarPrimerResultado();
        },
        confirmarSerialLote() {
            if (this._pendingItem) {
                this._pendingItem.serial_seleccionado = this.serialSeleccionado || null;
                this._pendingItem.lote_seleccionado = this.loteSeleccionado || null;
                this.items.push({ ...this._pendingItem });
                this._pendingItem = null;
            }
            this.modalSerialLote = false;
        },
        cancelarSerialLote() {
            this._pendingItem = null;
            this.modalSerialLote = false;
        },
        diasParaVencer(fecha) {
            if (!fecha) return 999;
            const diff = new Date(fecha) - new Date();
            return Math.max(0, Math.round(diff / (1000 * 60 * 60 * 24)));
        },
        cambiarPresentacionItem(item) {
            if (item.presentacion_id === 'base') {
                item.precio_unitario = item.precio_unitario_base;
            } else {
                const pres = item.presentaciones_disponibles.find(p => p.id == item.presentacion_id);
                if (pres) {
                    item.precio_unitario = Number(pres.precio_a || (item.precio_unitario_base * pres.factor_conversion));
                }
            }
        },
        eliminarItem(index) {
            this.items.splice(index, 1);
        },
        abrirModalCobro() {
            if (this.items.length === 0) return;
            this.cobro.efectivo_usd = Number(this.totales.total.toFixed(2));
            this.cobro.efectivo_ves = 0;
            this.cobro.pago_movil = 0;
            this.cobro.punto_venta = 0;
            this.cobro.zelle = 0;
            this.cobro.transferencia = 0;
            this.calcularVuelto();
            this.modalCobroAbierto = true;
        },
        calcularVuelto() {
            const totalPagadoUsd = (Number(this.cobro.efectivo_usd || 0)) +
                                   (Number(this.cobro.efectivo_ves || 0) / this.tasa) +
                                   (Number(this.cobro.pago_movil || 0) / this.tasa) +
                                   Number(this.cobro.punto_venta || 0) +
                                   Number(this.cobro.zelle || 0) +
                                   Number(this.cobro.transferencia || 0);
            this.cobro.vuelto_usd = Math.max(0, totalPagadoUsd - this.totales.total);
        },
        get totales() {
            let subtotal = 0, exento = 0, baseImponible = 0, iva = 0;
            this.items.forEach(i => {
                let st = i.cantidad * i.precio_unitario;
                subtotal += st;
                if (i.porcentaje_iva > 0 && !i.exento_iva) {
                    baseImponible += st;
                    iva += st * (i.porcentaje_iva / 100);
                } else {
                    exento += st;
                }
            });
            // IGTF 3% aplica sobre pagos en divisas extranjeras (USD, Zelle, etc.)
            const montoEnDivisas = Number(this.cobro.efectivo_usd || 0) + Number(this.cobro.zelle || 0) + Number(this.cobro.punto_venta || 0);
            const igtf = montoEnDivisas > 0 ? Math.min(subtotal + iva, montoEnDivisas) * 0.03 : 0;
            return { subtotal, exento, baseImponible, iva, igtf, total: subtotal + iva + igtf };
        },
        formatMoney(val) {
            return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        async procesarFactura() {
            if (this.items.length === 0) return;
            this.procesando = true;
            try {
                const formasPago = [];
                if (this.cobro.efectivo_usd > 0) formasPago.push({ forma_pago: 'EFECTIVO', monto: this.cobro.efectivo_usd, cuenta_id: 1, referencia: 'EFECTIVO-USD' });
                if (this.cobro.efectivo_ves > 0) formasPago.push({ forma_pago: 'EFECTIVO', monto: this.cobro.efectivo_ves / this.tasa, cuenta_id: 1, referencia: 'EFECTIVO-VES' });
                if (this.cobro.pago_movil > 0) formasPago.push({ forma_pago: 'PAGO_MOVIL', monto: this.cobro.pago_movil / this.tasa, cuenta_id: 1, referencia: 'C2P-PM' });
                if (this.cobro.punto_venta > 0) formasPago.push({ forma_pago: 'PUNTO_VENTA', monto: this.cobro.punto_venta, cuenta_id: 1, referencia: 'POS-CARD' });
                if (this.cobro.zelle > 0) formasPago.push({ forma_pago: 'ZELLE', monto: this.cobro.zelle, cuenta_id: 1, referencia: 'ZELLE-PAY' });
                if (this.cobro.transferencia > 0) formasPago.push({ forma_pago: 'TRANSFERENCIA', monto: this.cobro.transferencia, cuenta_id: 1, referencia: 'TRANSF-BANK' });

                const payload = {
                    cabecera: {
                        tipo_documento: this.cabecera.tipo_documento,
                        numero_documento: this.cabecera.numero_documento,
                        cliente_id: this.cabecera.cliente_id,
                        vendedor_id: this.cabecera.vendedor_id,
                        deposito_id: this.cabecera.deposito_id,
                        usuario_id: 1,
                        moneda_id: 1,
                        tasa_cambio: this.tasa,
                        condicion_pago: this.cabecera.condicion_pago,
                        formas_pago: formasPago
                    },
                    items: this.items.map(i => ({
                        producto_id: i.producto_id,
                        cantidad: i.cantidad,
                        precio_unitario: i.precio_unitario,
                        porcentaje_descuento: 0,
                        porcentaje_iva: i.porcentaje_iva,
                        seriales: i.seriales_asignados || (i.serial_seleccionado ? [i.serial_seleccionado] : [])
                    }))
                };

                const res = await fetch('/api/pos/procesar-venta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    const ventaId = data.data?.venta_id;
                    this.modalCobroAbierto = false;
                    this.items = [];
                    this.cabecera.numero_documento = 'FAC-' + Math.floor(Math.random() * 90000 + 10000);
                    // Abrir impresión en nueva ventana
                    if (ventaId) {
                        const printWindow = window.open(`/api/ventas/${ventaId}/documento`, '_blank');
                        if (!printWindow) {
                            const imprimir = confirm(`✅ ${this.cabecera.tipo_documento || 'Documento'} procesado con éxito.\n\n¿Desea imprimir el documento?`);
                            if (imprimir) window.open(`/api/ventas/${ventaId}/documento`, '_blank');
                        }
                    } else {
                        alert('✅ Venta procesada exitosamente.');
                    }
                } else {
                    alert('Error: ' + (data.message || data.mensaje));
                }
            } catch(e) {
                alert('Error de conexión: ' + e.message);
            } finally {
                this.procesando = false;
            }
        },
        async imprimirCierreZ() {
            try {
                const res = await fetch('/api/pos/resumen-cierre-z');
                const json = await res.json();
                alert(`Cierre Z Diario:\nTotal Ventas: ${json.data?.resumen_ventas?.length || 0}\nTotal Recaudado: $${json.data?.total_facturado_usd || 0}`);
            } catch(e) {
                alert('Error al consultar Cierre Z: ' + e.message);
            }
        },
        // Legacy - keep for backward compat
        cargarPedidosWeb() {
            this.abrirPanelPedidosWeb();
        },
        abrirPanelPedidosWeb() {
            this.panelPedidosWebAbierto = !this.panelPedidosWebAbierto;
            if (this.panelPedidosWebAbierto) this.actualizarContadorPedidosWeb();
        },
        async actualizarContadorPedidosWeb() {
            try {
                const res = await fetch('/api/ecommerce/pedidos-por-facturar');
                const json = await res.json();
                this.pedidosWebCount = (json.data || []).length;
            } catch(e) {
                this.pedidosWebCount = 0;
            }
        },
        // --- Escaneo masivo de seriales (pistola de código de barras) ---
        abrirEscaneoSeriales() {
            const item = this.itemSerialActual;
            if (!item) return;
            this.productoSerialActual = { ...item, cantidad: Math.max(1, Number(item.cantidad || 1)) };
            this.serialesEscaneados = [];
            this.inputSerial = '';
            this.modalSerialLote = false; // El escáner reemplaza al modal simple
            this.modalSerialesAbierto = true;
        },
        agregarSerialEscaneado() {
            const s = this.inputSerial.trim();
            if (!s) return;
            if (this.serialesEscaneados.includes(s)) {
                alert('El serial ' + s + ' ya fue escaneado.');
                this.inputSerial = '';
                return;
            }
            if (this.productoSerialActual && this.serialesEscaneados.length >= this.productoSerialActual.cantidad) {
                alert('Ya alcanzó la cantidad del renglón (' + this.productoSerialActual.cantidad + ').');
                return;
            }
            this.serialesEscaneados.push(s);
            this.inputSerial = '';
        },
        cancelarAsignacionSeriales() {
            this.modalSerialesAbierto = false;
            this.serialesEscaneados = [];
            this.inputSerial = '';
            this.productoSerialActual = null;
            this.modalSerialLote = true; // Vuelve al modal de selección simple
        },
        confirmarSerialesRenglon() {
            if (!this.productoSerialActual || this.serialesEscaneados.length !== this.productoSerialActual.cantidad) return;
            if (this._pendingItem) {
                this._pendingItem.cantidad = this.productoSerialActual.cantidad;
                this._pendingItem.serial_seleccionado = this.serialesEscaneados[0];
                this._pendingItem.seriales_asignados = [...this.serialesEscaneados];
                this.items.push({ ...this._pendingItem });
                this._pendingItem = null;
            }
            this.modalSerialLote = false;
            this.modalSerialesAbierto = false;
            this.serialesEscaneados = [];
            this.inputSerial = '';
            this.productoSerialActual = null;
        }
    }
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>