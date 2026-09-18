<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
   <title>Preventa Móvil - Ruta</title>
   <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-100 text-gray-800 font-sans select-none" x-data="preventaApp()">
   <!-- Cabecera Móvil -->
   <header class="bg-blue-700 text-white p-4 sticky top-0 z-30 shadow-md flex justify-between items-center">
       <div>
           <h1 class="text-base font-bold flex items-center gap-2">
               <i class="fa-solid fa-truck-fast"></i> Preventa en Ruta
           </h1>
           <p class="text-xs text-blue-200" x-text="'Vendedor: ' + vendedor.nombre"></p>
       </div>
       <button @click="sincronizar()" class="bg-blue-800 p-2 rounded-lg text-xs flex items-center gap-1 active:scale-95 transition">
           <i class="fa-solid fa-rotate" :class="sincronizando ? 'fa-spin' : ''"></i> Sync
       </button>
   </header>
   <!-- Contenedor Principal -->
   <main class="p-3 pb-24 space-y-3">

       <!-- Selección de Cliente -->
       <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-200">
           <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase">Cliente de la Ruta</label>
           <select x-model="pedido.cliente_id" @change="actualizarListaPrecioCliente()" class="w-full text-sm border-gray-300 rounded-lg p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
               <option value="">-- Seleccionar Cliente --</option>
               <template x-for="c in clientes" :key="c.id">
                   <option :value="c.id" x-text="c.razon_social + ' (' + c.documento_fiscal + ')'"></option>
               </template>
           </select>
       </div>
       <!-- Catálogo de Productos para Agregar -->
       <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-200">
           <div class="relative mb-2">
               <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                   <i class="fa-solid fa-magnifying-glass"></i>
               </span>
               <input type="text" x-model="filtroProducto" placeholder="Buscar por código o nombre..." class="w-full pl-8 pr-2 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
           </div>
           <div class="divide-y divide-gray-100 max-h-60 overflow-y-auto">
               <template x-for="p in productosFiltrados" :key="p.id">
                   <div class="py-2.5 flex justify-between items-center gap-2">
                       <div class="flex-1">
                           <p class="text-xs font-bold text-gray-900 leading-tight" x-text="p.descripcion"></p>
                           <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-0.5">
                               <span class="font-mono text-blue-600 font-bold" x-text="'$' + p.precio_actual.toFixed(2)"></span>
                               <span>•</span>
                               <span :class="p.stock_disponible > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'Disponible: ' + p.stock_disponible"></span>
                           </div>
                       </div>
                       <button @click="agregarAlPedido(p)" :disabled="p.stock_disponible <= 0" class="bg-blue-600 active:bg-blue-700 text-white rounded-lg p-2 disabled:opacity-40 transition">
                           <i class="fa-solid fa-plus text-xs"></i>
                       </button>

                   </div>
               </template>
           </div>
       </div>
       <!-- Resumen de Ítems en el Pedido -->
       <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-200" x-show="pedido.items.length > 0">
           <h3 class="font-bold text-xs uppercase text-gray-500 mb-2">Ítems del Pedido (<span x-text="pedido.items.length"></span>)</h3>
           <div class="space-y-2">
               <template x-for="(item, idx) in pedido.items" :key="idx">
                   <div class="bg-gray-50 p-2.5 rounded-lg flex justify-between items-center border">
                       <div class="flex-1 pr-2">
                           <p class="text-xs font-semibold text-gray-800" x-text="item.descripcion"></p>
                           <p class="text-[11px] text-gray-500 font-mono" x-text="'$' + item.precio_unitario + ' c/u'"></p>
                       </div>
                       <div class="flex items-center gap-2">
                           <input type="number" min="1" :max="item.max_stock" x-model.number="item.cantidad" class="w-14 border rounded text-center p-1 text-xs">
                           <button @click="eliminarItem(idx)" class="text-red-500 p-1"><i class="fa-solid fa-trash-can"></i></button>
                       </div>
                   </div>
               </template>
           </div>
       </div>
   </main>
   <!-- Barra Inferior de Acción y Transmisión -->
   <footer class="fixed bottom-0 inset-x-0 bg-white border-t p-3 flex items-center justify-between shadow-lg z-30">
       <div>
           <span class="text-[10px] text-gray-400 block uppercase font-bold">Total a Comprometer</span>
           <span class="text-base font-extrabold font-mono text-blue-900" x-text="'$' + totalPedido.toFixed(2)"></span>
       </div>
       <button @click="transmitirApartado()" :disabled="pedido.items.length === 0 || !pedido.cliente_id || transmitiendo" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold px-5 py-2.5 rounded-xl text-xs flex items-center gap-2 transition">
           <i class="fa-solid fa-cloud-arrow-up" x-show="!transmitiendo"></i>
           <i class="fa-solid fa-spinner fa-spin" x-show="transmitiendo"></i>
           <span>Comprometer Stock</span>
       </button>
   </footer>
   <script>
   function preventaApp() {
       return {
           vendedor: { id: 1, nombre: 'Carlos Rodríguez (Ruta Centro)' },
           sincronizando: false,
           transmitiendo: false,
           filtroProducto: '',
           clientes: [],
           productos: [],
           pedido: {
               cliente_id: '',
               vendedor_id: 1,
               items: []
           },
           init() {
               this.sincronizar();
           },
           sincronizar() {
               this.sincronizando = true;
               fetch(`/api/preventa/sincronizar?vendedor_id=${this.vendedor.id}`)
                   .then(r => r.json())
                   .then(res => {
                       this.clientes = res.data.clientes || [];
                       this.productos = (res.data.productos || []).map(p => ({
                           ...p,
                           precio_actual: Number(p.precio_a),
                           stock_disponible: Number(p.stock_disponible)
                       }));
                   })
                   .finally(() => this.sincronizando = false);
           },
           get productosFiltrados() {

               if (!this.filtroProducto.trim()) return this.productos;
               const txt = this.filtroProducto.toLowerCase();
               return this.productos.filter(p =>
                   p.descripcion.toLowerCase().includes(txt) ||
                   p.codigo.toLowerCase().includes(txt)
               );
           },
           get totalPedido() {
               return this.pedido.items.reduce((acc, item) => acc + (item.cantidad * item.precio_unitario), 0);
           },
           actualizarListaPrecioCliente() {
               const cli = this.clientes.find(c => c.id == this.pedido.cliente_id);
               if (!cli) return;
               const lista = (cli.lista_precio_default || 'A').toLowerCase();
               this.productos.forEach(p => {
                   p.precio_actual = Number(p['precio_' + lista] || p.precio_a);
               });
           },
           agregarAlPedido(p) {
               const existe = this.pedido.items.find(i => i.producto_id === p.id);
               if (existe) {
                   if (existe.cantidad < p.stock_disponible) existe.cantidad++;
               } else {
                   this.pedido.items.push({
                       producto_id: p.id,
                       descripcion: p.descripcion,
                       cantidad: 1,
                       precio_unitario: p.precio_actual,
                       max_stock: p.stock_disponible
                   });
               }
           },
           eliminarItem(idx) {
               this.pedido.items.splice(idx, 1);
           },
           async transmitirApartado() {
               this.transmitiendo = true;
               try {
                   const res = await fetch('/api/preventa/enviar-pedido', {
                       method: 'POST',
                       headers: { 'Content-Type': 'application/json' },
                       body: JSON.stringify(this.pedido)
                   });
                   const data = await res.json();
                   if (data.status === 'success') {
                       alert('Pedido cargado con éxito. El inventario ha quedado comprometido en el almacén.');
                       this.pedido.items = [];
                       this.pedido.cliente_id = '';
                       this.sincronizar(); // Actualizar existencias locales
                   } else {
                       throw new Error(data.message);
                   }
               } catch (e) {
                   alert('Error transmitiendo pedido: ' + e.message);
               } finally {
                   this.transmitiendo = false;
               }
           }
       }
   }
   </script>
</body>
</html>
