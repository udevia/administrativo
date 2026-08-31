<?php
$pageTitle = 'Ingresar Factura de Compra - mi ERP';
$activeMenu = 'compras_nueva';
ob_start();
?>
<div class="space-y-4" x-data="comprasApp()" x-init="initApp()" x-cloak>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs">Módulo Inventario</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Ingreso de Factura de Compra</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Registra entradas de inventario afectando costos, existencias y CxP.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="procesarFactura()" :disabled="procesando || !esValida()" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-save" x-show="!procesando"></i>
                <i class="fa-solid fa-spinner fa-spin" x-show="procesando"></i> Procesar y Cargar Inventario
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- CABECERA -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h3 class="text-sm font-bold text-slate-800 mb-4 border-b pb-2">Datos de Cabecera</h3>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Proveedor</label>
                        <select x-model="cabecera.proveedor_id" class="w-full text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione Proveedor...</option>
                            <template x-for="p in proveedores" :key="p.id">
                                <option :value="p.id" x-text="p.razon_social + ' (' + p.documento_fiscal + ')'"></option>
                            </template>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Depósito / Almacén</label>
                        <select x-model="cabecera.deposito_id" class="w-full text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <template x-for="d in depositos" :key="d.id">
                                <option :value="d.id" x-text="d.nombre"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Nro. Factura Proveedor</label>
                        <input type="text" x-model="cabecera.numero_factura" class="w-full text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: 001-99238">
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Condición de Pago</label>
                        <select x-model="cabecera.condicion_pago" class="w-full text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="CONTADO">Contado</option>
                            <option value="CREDITO_7">Crédito 7 Días</option>
                            <option value="CREDITO_15">Crédito 15 Días</option>
                            <option value="CREDITO_30">Crédito 30 Días</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- TOTALES -->
            <div class="bg-slate-800 text-white rounded-2xl shadow-sm border border-slate-700 p-5">
                <h3 class="text-sm font-bold text-slate-300 mb-4 border-b border-slate-600 pb-2">Resumen Factura</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Items Totales:</span>
                        <span class="font-bold" x-text="items.length"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Unidades:</span>
                        <span class="font-bold" x-text="calcularUnidades()"></span>
                    </div>
                    <div class="border-t border-slate-600 my-2 pt-2 flex justify-between items-end">
                        <span class="text-slate-400 text-xs">MONTO TOTAL ($)</span>
                        <span class="text-2xl font-black text-emerald-400" x-text="fmt(calcularTotal())"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ITEMS -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden h-full flex flex-col">
                <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center gap-2">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-search absolute left-3 top-3 text-slate-400"></i>
                        <input type="text" x-model="buscar" @input.debounce.300ms="filtrarProductos" placeholder="Buscar por código o nombre para agregar..." class="w-full pl-9 pr-4 py-2 border-slate-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                        
                        <!-- Dropdown Búsqueda -->
                        <div x-show="resultadosBusqueda.length > 0" @click.away="resultadosBusqueda = []" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="prod in resultadosBusqueda" :key="prod.id">
                                <div @click="agregarItem(prod)" class="p-3 hover:bg-slate-50 cursor-pointer border-b last:border-0 flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-sm text-slate-800" x-text="prod.nombre || prod.descripcion"></div>
                                        <div class="text-xs text-slate-500" x-text="prod.codigo"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-semibold text-slate-600">Últ. Costo: $<span x-text="fmt(prod.costo_promedio_usd || 0)"></span></div>
                                        <span x-show="prod.maneja_seriales == 1" class="text-[10px] bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-bold">Requiere Serial</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 font-semibold text-xs uppercase w-10"></th>
                                <th class="px-4 py-3 font-semibold text-xs uppercase">Producto</th>
                                <th class="px-4 py-3 font-semibold text-xs uppercase w-32">Cant.</th>
                                <th class="px-4 py-3 font-semibold text-xs uppercase w-32">Costo Unit. ($)</th>
                                <th class="px-4 py-3 font-semibold text-xs uppercase w-32">Subtotal ($)</th>
                                <th class="px-4 py-3 font-semibold text-xs uppercase w-20">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr x-show="items.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-box-open text-3xl mb-2"></i>
                                    <p>No hay productos en la factura.</p>
                                </td>
                            </tr>
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr>
                                    <td class="px-4 py-3 text-slate-400 text-center" x-text="idx+1"></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-800" x-text="item.nombre"></div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-slate-500 font-mono" x-text="item.codigo"></span>
                                            
                                            <!-- Boton Seriales -->
                                            <button x-show="item.maneja_seriales" @click="abrirModalSeriales(idx)" 
                                                    :class="item.seriales.length == item.cantidad ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'"
                                                    class="text-[10px] font-bold px-2 py-0.5 rounded transition shadow-sm">
                                                <i class="fa-solid fa-barcode mr-1"></i> Seriales (<span x-text="item.seriales.length"></span>/<span x-text="item.cantidad"></span>)
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" min="1" step="0.01" x-model.number="item.cantidad" class="w-full p-1.5 text-sm border-slate-200 rounded text-right">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" min="0" step="0.01" x-model.number="item.costo_unitario" class="w-full p-1.5 text-sm border-slate-200 rounded text-right">
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-700">
                                        $<span x-text="fmt((item.cantidad || 0) * (item.costo_unitario || 0))"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="removerItem(idx)" class="text-red-500 hover:text-red-700 transition" title="Remover">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE SERIALES -->
    <div x-show="modalSeriales" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]" x-show="modalSeriales" @click.away="cerrarModalSeriales()" x-transition.scale.95>
            
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg">Ingreso de Seriales / IMEI</h3>
                    <p class="text-xs text-slate-500" x-text="'Producto: ' + (itemActual ? itemActual.nombre : '')"></p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-lg text-sm font-bold shadow-inner">
                        Ingresados: <span x-text="serialesInput.length"></span> / <span x-text="itemActual ? itemActual.cantidad : 0"></span>
                    </div>
                    <button @click="cerrarModalSeriales()" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-5 flex-1 overflow-y-auto">
                <!-- Selector de Modo de Ingreso -->
                <div class="flex p-1 bg-slate-100 rounded-xl w-max mx-auto mb-5 shadow-inner">
                    <button @click="modoIngresoSerial = 'lista'" :class="modoIngresoSerial === 'lista' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-lg text-sm font-bold transition flex items-center gap-2">
                        <i class="fa-solid fa-list-ol"></i> 1 a 1 (Escáner)
                    </button>
                    <button @click="modoIngresoSerial = 'masivo'" :class="modoIngresoSerial === 'masivo' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-lg text-sm font-bold transition flex items-center gap-2">
                        <i class="fa-solid fa-paste"></i> Masivo (Texto)
                    </button>
                </div>

                <!-- MODO LISTA (1 a 1) -->
                <div x-show="modoIngresoSerial === 'lista'" class="space-y-4">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-barcode absolute left-3 top-2.5 text-slate-400"></i>
                            <input type="text" x-model="nuevoSerial" @keydown.enter="agregarSerialLista" x-ref="inputSerial" placeholder="Escanee o escriba el serial y presione Enter..." class="w-full pl-9 pr-4 py-2 border-slate-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                        </div>
                        <button @click="agregarSerialLista" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl transition">Agregar</button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4">
                        <template x-for="(ser, sIdx) in serialesInput" :key="sIdx">
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-sm flex justify-between items-center group shadow-sm">
                                <span class="font-mono text-slate-700" x-text="ser"></span>
                                <button @click="serialesInput.splice(sIdx, 1)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- MODO MASIVO (TEXTAREA) -->
                <div x-show="modoIngresoSerial === 'masivo'">
                    <p class="text-xs text-slate-500 mb-2">Pegue la lista de seriales copiados desde Excel. Puede usar comas, tabulaciones o saltos de línea para separarlos.</p>
                    <textarea x-model="textareaSeriales" rows="8" class="w-full border-slate-300 rounded-xl text-sm font-mono p-3 focus:ring-blue-500 focus:border-blue-500 shadow-sm" placeholder="SN-001&#10;SN-002&#10;SN-003"></textarea>
                    <div class="mt-2 text-right">
                        <button @click="procesarTextareaSeriales" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-lg transition shadow-sm">
                            <i class="fa-solid fa-bolt mr-1"></i> Extraer y Validar
                        </button>
                    </div>
                </div>

                <!-- ALERTAS -->
                <div x-show="serialesInput.length > (itemActual ? itemActual.cantidad : 0)" class="mt-4 p-3 bg-red-50 text-red-700 border border-red-200 rounded-xl text-sm flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <div>
                        <strong class="block">¡Atención! Exceso de seriales.</strong>
                        Has ingresado <span x-text="serialesInput.length"></span> seriales, pero la cantidad del producto es <span x-text="itemActual ? itemActual.cantidad : 0"></span>. Por favor elimina los sobrantes o ajusta la cantidad en la factura.
                    </div>
                </div>
            </div>
            
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-2">
                <button @click="cerrarModalSeriales()" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition">Cancelar</button>
                <button @click="guardarSerialesEnItem()" :disabled="serialesInput.length != (itemActual ? itemActual.cantidad : 0)" class="px-4 py-2 text-sm font-bold bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl shadow-sm transition">Confirmar Seriales</button>
            </div>
        </div>
    </div>
</div>

<script>
function comprasApp() {
    return {
        proveedores: [],
        depositos: [],
        productosBD: [],
        
        cabecera: {
            proveedor_id: '',
            deposito_id: 1,
            numero_documento: '',
            numero_factura: '',
            tipo_documento: 'FACTURA',
            condicion_pago: 'CONTADO'
        },
        items: [],
        
        buscar: '',
        resultadosBusqueda: [],
        procesando: false,

        // Modal Seriales
        modalSeriales: false,
        itemActualIndex: -1,
        itemActual: null,
        modoIngresoSerial: 'lista', // lista o masivo
        serialesInput: [],
        nuevoSerial: '',
        textareaSeriales: '',

        async initApp() {
            await Promise.all([
                this.cargarProveedores(),
                this.cargarDepositos(),
                this.cargarProductos()
            ]);
        },

        async cargarProveedores() {
            let res = await fetch('/api/maestros/proveedores').then(r => r.json());
            if(res.status === 'success') this.proveedores = res.data;
        },
        async cargarDepositos() {
            let res = await fetch('/api/maestros/depositos').then(r => r.json());
            if(res.status === 'success') this.depositos = res.data;
        },
        async cargarProductos() {
            let res = await fetch('/api/maestros/productos').then(r => r.json());
            if(res.status === 'success') this.productosBD = res.data;
        },

        filtrarProductos() {
            if (this.buscar.trim().length < 2) {
                this.resultadosBusqueda = [];
                return;
            }
            const q = this.buscar.toLowerCase();
            this.resultadosBusqueda = this.productosBD.filter(p => 
                (p.codigo && p.codigo.toLowerCase().includes(q)) || 
                ((p.nombre || p.descripcion) && (p.nombre || p.descripcion).toLowerCase().includes(q))
            ).slice(0, 10); // Max 10 resultados
        },

        agregarItem(prod) {
            this.items.push({
                producto_id: prod.id,
                codigo: prod.codigo,
                nombre: prod.nombre || prod.descripcion,
                cantidad: 1,
                costo_unitario: Number(prod.costo_promedio_usd || 0),
                maneja_seriales: prod.maneja_seriales == 1,
                seriales: [] // Arreglo de strings
            });
            this.buscar = '';
            this.resultadosBusqueda = [];
        },

        removerItem(idx) {
            this.items.splice(idx, 1);
        },

        calcularUnidades() {
            return this.items.reduce((a, b) => a + Number(b.cantidad || 0), 0);
        },
        calcularTotal() {
            return this.items.reduce((a, b) => a + (Number(b.cantidad || 0) * Number(b.costo_unitario || 0)), 0);
        },
        fmt(num) {
            return Number(num).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        },

        // --- Gestión de Seriales ---
        abrirModalSeriales(idx) {
            this.itemActualIndex = idx;
            this.itemActual = this.items[idx];
            // Clonar seriales para edición
            this.serialesInput = [...this.itemActual.seriales];
            this.modoIngresoSerial = 'lista';
            this.nuevoSerial = '';
            this.textareaSeriales = this.serialesInput.join('\n');
            this.modalSeriales = true;
            
            setTimeout(() => {
                if(this.$refs.inputSerial) this.$refs.inputSerial.focus();
            }, 100);
        },
        cerrarModalSeriales() {
            this.modalSeriales = false;
            this.itemActual = null;
            this.itemActualIndex = -1;
        },
        agregarSerialLista() {
            let s = this.nuevoSerial.trim();
            if(s) {
                if(!this.serialesInput.includes(s)) {
                    this.serialesInput.push(s);
                }
                this.nuevoSerial = '';
            }
        },
        procesarTextareaSeriales() {
            if(!this.textareaSeriales.trim()) return;
            // Separar por salto de línea, coma o tabulación
            let raw = this.textareaSeriales.split(/[\n,\t]+/);
            let unicos = new Set(this.serialesInput);
            
            raw.forEach(v => {
                let s = v.trim();
                if(s) unicos.add(s);
            });
            
            this.serialesInput = Array.from(unicos);
            this.textareaSeriales = '';
            this.modoIngresoSerial = 'lista'; // Regresar a vista lista para ver el resultado
        },
        guardarSerialesEnItem() {
            if(this.serialesInput.length !== Number(this.itemActual.cantidad)) {
                alert(`Debe ingresar exactamente ${this.itemActual.cantidad} seriales.`);
                return;
            }
            this.items[this.itemActualIndex].seriales = [...this.serialesInput];
            this.cerrarModalSeriales();
        },

        // --- Procesamiento de Factura ---
        esValida() {
            if(!this.cabecera.proveedor_id || !this.cabecera.numero_factura || this.items.length === 0) return false;
            // Validar que los seriales estén completos si el producto los maneja
            for(let i=0; i<this.items.length; i++) {
                if(this.items[i].maneja_seriales && this.items[i].seriales.length !== Number(this.items[i].cantidad)) {
                    return false;
                }
            }
            return true;
        },

        async procesarFactura() {
            if(!this.esValida()) return;
            
            this.procesando = true;
            
            // Construir payload formato API (similar a E2E Python)
            let payload = {
                cabecera: {
                    proveedor_id: this.cabecera.proveedor_id,
                    deposito_id: this.cabecera.deposito_id,
                    numero_documento: this.cabecera.numero_factura,
                    documento_referencia: this.cabecera.numero_factura,
                    numero_factura: this.cabecera.numero_factura,
                    total: this.calcularTotal(),
                    tipo_documento: this.cabecera.tipo_documento,
                    condicion_pago: this.cabecera.condicion_pago
                },
                items: this.items.map(it => {
                    return {
                        producto_id: it.producto_id,
                        cantidad: Number(it.cantidad),
                        costo_unitario: Number(it.costo_unitario),
                        seriales: it.maneja_seriales ? it.seriales.map(s => ({numero_serial: s})) : []
                    }
                })
            };

            try {
                let res = await fetch('/api/compras/procesar-factura', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                let data = await res.json();
                
                if(data.status === 'success') {
                    alert('¡Factura de compra procesada exitosamente! Inventario actualizado.');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || data.mensaje || JSON.stringify(data)));
                }
            } catch(e) {
                alert('Error de conexión.');
            } finally {
                this.procesando = false;
            }
        }
    }
}
</script>

<?php
$slot = ob_get_clean();
require ROOT_PATH . '/views/layout.php';
?>
