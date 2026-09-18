<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Pedido Confirmado - Mi Tienda Online</title>
   <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans">
   <div class="min-h-screen flex items-center justify-center p-4">
       <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 max-w-md w-full text-center space-y-4">
           <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center">
               <i class="fa-solid fa-circle-check text-3xl text-emerald-600"></i>
           </div>
           <h1 class="text-xl font-black text-slate-900">¡Pago Aprobado!</h1>
           <p class="text-xs text-slate-500 leading-relaxed">
               Su pago C2P fue validado y debitado exitosamente por el banco.
               El pedido ha quedado confirmado y su inventario comprometido en el almacén.
               Un asesor le contactará para coordinar la entrega.
           </p>
           <a href="/tienda" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">
               <i class="fa-solid fa-store"></i> Volver a la Tienda
           </a>
       </div>
   </div>
</body>
</html>
