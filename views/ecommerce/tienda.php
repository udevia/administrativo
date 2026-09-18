<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Tienda Online Oficial</title>
   <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans" x-data="ecommerceApp()">
   <!-- Barra de Navegación -->
   <header class="bg-white border-b sticky top-0 z-40 shadow-sm">
       <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center gap-4">
           <div class="flex items-center gap-2">
               <i class="fa-solid fa-store text-blue-600 text-2xl"></i>
               <span class="font-extrabold text-lg text-slate-900 tracking-tight">Mi Tienda Online</span>
           </div>
           <div class="flex-1 max-w-md hidden md:block">
               <input type="text" x-model="busqueda" @input.debounce.300ms="cargarCatalogo()" placeholder="Buscar productos..." class="w-full bg-slate-100 border-0 rounded-full px-4 py-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
           </div>
           <div class="flex items-center gap-4">
               <button @click="abrirCarrito = true" class="relative bg-blue-50 text-blue-600 p-2.5 rounded-full hover:bg-blue-100 transition">
                   <i class="fa-solid fa-cart-shopping text-base"></i>
                   <span x-show="carrito.length > 0" class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] rounded-full w-5 h-5 flex items-center justify-center" x-text="carrito.length"></span>
               </button>
           </div>

       </div>
   </header>
   <!-- Catálogo de Productos -->
   <main class="max-w-7xl mx-auto px-4 py-6">
       <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
           <template x-for="p in productos" :key="p.id">
               <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col p-3">
                   <div>
                       <div class="h-32 bg-slate-100 rounded-xl mb-3 flex items-center justify-center overflow-hidden">
                           <template x-if="p.imagen_url">
                               <img :src="p.imagen_url" class="h-full w-full object-cover">
                           </template>
                           <template x-if="!p.imagen_url">
                               <i class="fa-solid fa-box text-3xl text-slate-300"></i>
                           </template>
                       </div>
                       <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5" x-text="p.categoria_nombre"></span>
                       <h3 class="font-bold text-xs text-slate-900 line-clamp-2 leading-snug" x-text="p.descripcion"></h3>
                   </div>
                   <div class="mt-3 pt-3 border-t border-slate-100">
                       <div class="flex justify-between items-baseline mb-2">
                           <span class="font-extrabold font-mono text-sm text-blue-900" x-text="'$' + Number(p.precio_venta).toFixed(2)"></span>
                           <span class="text-[10px] text-slate-400 font-mono" x-text="'Ref: Bs. ' + (p.precio_venta * tasa).toFixed(2)"></span>
                       </div>
                       <button @click="agregarAlCarrito(p)" class="w-full bg-blue-600 hover:bg-blue-700 active:scale-95 transition rounded-xl py-2 text-xs font-bold text-white flex items-center justify-center gap-1.5">
                           <i class="fa-solid fa-plus text-[10px]"></i> Agregar
                       </button>
                   </div>
               </div>
           </template>
       </div>
   </main>
   <!-- Drawer Lateral del Carrito y Checkout -->
   <div x-show="abrirCarrito" class="fixed inset-0 z-50 overflow-hidden" x-cloak>
       <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="abrirCarrito = false"></div>
       <div class="absolute inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl flex flex-col">
           <div class="p-4 border-b flex justify-between items-center bg-slate-50">
               <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                   <i class="fa-solid fa-bag-shopping text-blue-600"></i> Carrito de Compras
               </h3>
               <button @click="abrirCarrito = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
           </div>
           <!-- Ítems -->
           <div class="p-4 flex-1 overflow-y-auto space-y-3">
               <template x-for="(item, idx) in carrito" :key="idx">
                   <div class="flex justify-between items-center p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                       <div class="flex-1 pr-2">
                           <p class="text-xs font-bold text-slate-900 leading-tight" x-text="item.descripcion"></p>
                           <span class="text-xs font-mono font-bold text-blue-700" x-text="'$' + (item.precio_venta * item.cantidad).toFixed(2)"></span>
                       </div>
                       <div class="flex items-center gap-2">
                           <input type="number" min="1" x-model.number="item.cantidad" class="w-12 border rounded-lg p-1 text-center text-xs">
                           <button @click="carrito.splice(idx, 1)" class="text-red-500 hover:text-red-700 p-1"><i class="fa-solid fa-trash-can text-xs"></i></button>
                       </div>
                   </div>
               </template>
               <div x-show="carrito.length === 0" class="text-center py-16 text-slate-400 text-xs">Tu carrito está vacío</div>
           </div>
           <!-- Formulario de Pago / Checkout -->
           <div class="p-4 border-t bg-slate-50 space-y-3" x-show="carrito.length > 0">
               <div class="space-y-1.5 text-xs font-mono">
                   <div class="flex justify-between font-bold text-sm text-slate-900">
                       <span>Total a Pagar:</span>
                       <span class="text-blue-900" x-text="'$' + totalCarrito.toFixed(2) + ' (Bs. ' + (totalCarrito * tasa).toFixed(2) + ')'"></span>
                   </div>
               </div>
               <div class="space-y-2 pt-2 border-t text-xs">

                   <label class="block font-bold text-slate-700 uppercase text-[10px]">Método de Pago:</label>
                   <select x-model="checkout.metodo_pago" class="w-full border rounded-lg p-2 text-xs bg-white font-medium">
                       <option value="PAGO_MOVIL">📲 Pago Móvil</option>
                       <option value="ZELLE">💵 Zelle</option>
                       <option value="TRANSFERENCIA_VES">🏦 Transferencia Bancaria</option>
                   </select>
                   <input type="text" x-model="checkout.referencia_pago" placeholder="N° de Referencia de Pago" class="w-full border rounded-lg p-2 text-xs">
               </div>
               <button @click="confirmarPedidoWeb()" :disabled="procesando || !checkout.referencia_pago" class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-2 text-xs transition">
                   <i class="fa-solid fa-lock" x-show="!procesando"></i>
                   <i class="fa-solid fa-spinner fa-spin" x-show="procesando"></i>
                   <span>Confirmar Compra y Reservar Stock</span>
               </button>
           </div>
       </div>
   </div>
   <script>
   function ecommerceApp() {
       return {
           productos: [],
           carrito: [],
           busqueda: '',
           tasa: <?= \App\Core\Database::getTasaActualUsd() ?>,
           abrirCarrito: false,
           procesando: false,
           checkout: {
               usuario_web_id: 1, // Simulado / Sesión activa
               metodo_pago: 'PAGO_MOVIL',
               referencia_pago: ''
           },
           init() {
               this.cargarCatalogo();
           },
           cargarCatalogo() {
               fetch(`/api/ecommerce/catalogo?q=${encodeURIComponent(this.busqueda)}`)
                   .then(r => r.json())
                   .then(res => this.productos = res.data || []);
           },
           get totalCarrito() {
               return this.carrito.reduce((acc, i) => acc + (i.precio_venta * i.cantidad), 0);
           },
           agregarAlCarrito(p) {
               const existe = this.carrito.find(i => i.id === p.id);
               if (existe) {
                   existe.cantidad++;
               } else {
                   this.carrito.push({ ...p, cantidad: 1 });
               }
               this.abrirCarrito = true;
           },
           async confirmarPedidoWeb() {
               this.procesando = true;
               try {
                   const res = await fetch('/api/ecommerce/pedido', {
                       method: 'POST',
                       headers: { 'Content-Type': 'application/json' },
                       body: JSON.stringify({
                           usuario_web_id: this.checkout.usuario_web_id,
                           metodo_pago: this.checkout.metodo_pago,
                           referencia_pago: this.checkout.referencia_pago,
                           tasa_cambio: this.tasa,
                           items: this.carrito.map(i => ({
                               producto_id: i.id,
                               cantidad: i.cantidad,
                               precio_unitario: i.precio_venta
                           }))

                       })
                   });
                   const data = await res.json();
                   if (data.status === 'success') {
                       alert(`¡Pedido ${data.numero_orden} procesado con éxito! El inventario ha sido comprometido.`);
                       this.carrito = [];
                       this.abrirCarrito = false;
                       this.cargarCatalogo();
                   } else {
                       alert('Error: ' + data.message);
                   }
               } catch (e) {
                   alert('Error comunicando con el servidor: ' + e.message);
               } finally {
                   this.procesando = false;
               }
           }
       }
   }
   </script>
</body>
</html>
