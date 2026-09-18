<!-- Formulario Reactivo de Validación Bancaria en Vivo -->
<div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4" x-data="checkoutBancarioApp()">
   <div class="flex items-center justify-between border-b pb-3">
       <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
           <i class="fa-solid fa-mobile-screen-button text-blue-600"></i> Pago Móvil / Débito Inmediato (C2P)
       </h4>
       <span class="text-[11px] font-mono font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
           Validación en Tiempo Real
       </span>
   </div>
   <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
       <div>
           <label class="block font-semibold text-slate-600 mb-1">Banco del Pagador:</label>
           <select x-model="pago.banco_origen" class="w-full border rounded-xl p-2.5 bg-slate-50 font-medium">
               <option value="0134">0134 - Banesco Banco Universal</option>
               <option value="0138">0138 - Banco Plaza</option>
               <option value="0102">0102 - Banco de Venezuela</option>
               <option value="0108">0108 - Banco Provincial</option>
               <option value="0105">0105 - Banco Mercantil</option>
               <option value="0172">0172 - Bancamiga</option>
           </select>
       </div>
       <div>
           <label class="block font-semibold text-slate-600 mb-1">Cédula / RIF del Titular:</label>
           <div class="flex gap-1">
               <select x-model="pago.tipo_doc" class="border rounded-xl p-2 bg-slate-50 font-bold text-xs">
                   <option value="V">V</option>
                   <option value="J">J</option>
                   <option value="E">E</option>
               </select>

               <input type="text" x-model="pago.numero_cedula" placeholder="12345678" class="w-full border rounded-xl p-2.5 bg-slate-50 font-medium">
           </div>
       </div>
       <div>
           <label class="block font-semibold text-slate-600 mb-1">Teléfono Afiliado a Pago Móvil:</label>
           <input type="text" x-model="pago.telefono" placeholder="04141234567" class="w-full border rounded-xl p-2.5 bg-slate-50 font-medium">
       </div>
       <div>
           <label class="block font-semibold text-slate-600 mb-1">Clave de Pago / Token OTP (C2P):</label>
           <input type="password" maxlength="8" x-model="pago.token_otp" placeholder="Solicítela en su App bancaria" class="w-full border rounded-xl p-2.5 bg-slate-50 font-mono">
       </div>
   </div>
   <!-- Resumen de Monto en Bolívares y Tasa Oficial -->
   <div class="bg-blue-50/60 border border-blue-200 rounded-xl p-3 flex justify-between items-center text-xs font-mono">
       <div>
           <span class="text-slate-500 block text-[10px] uppercase font-bold">Total a Debitar:</span>
           <span class="text-base font-extrabold text-blue-950" x-text="'Bs. ' + (totalUsd * tasaBs).toFixed(2)"></span>
       </div>
       <div class="text-right">
           <span class="text-slate-400 block text-[10px]">Tasa de Cambio:</span>
           <span class="font-bold text-slate-700" x-text="'Bs. ' + tasaBs.toFixed(2) + ' / USD'"></span>
       </div>
   </div>
   <button @click="ejecutarPagoC2P()" :disabled="procesando || !pago.telefono || !pago.numero_cedula || !pago.token_otp" class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-2 text-xs transition">
       <i class="fa-solid fa-shield-check" x-show="!procesando"></i>
       <i class="fa-solid fa-spinner fa-spin" x-show="procesando"></i>
       <span x-text="procesando ? 'Conectando con el Banco...' : 'Validar y Debitar Pago Ahora'"></span>
   </button>
</div>
<script>
function checkoutBancarioApp() {
   return {
       totalUsd: 45.00,
       tasaBs: <?= \App\Core\Database::getTasaActualUsd() ?>,
       procesando: false,
       pago: {
           banco_origen: '0134',
           tipo_doc: 'V',
           numero_cedula: '',
           telefono: '',
           token_otp: '',
           pedido_web_id: 101
       },
       async ejecutarPagoC2P() {
           this.procesando = true;
           try {
               const res = await fetch('/api/ecommerce/pago-c2p', {
                   method: 'POST',
                   headers: { 'Content-Type': 'application/json' },
                   body: JSON.stringify({
                       banco_codigo: this.pago.banco_origen,
                       pedido_web_id: this.pago.pedido_web_id,
                       banco_origen: this.pago.banco_origen,
                       telefono_pagador: this.pago.telefono,
                       cedula_pagador: this.pago.tipo_doc + this.pago.numero_cedula,
                       token_otp: this.pago.token_otp,
                       monto_bs: (this.totalUsd * this.tasaBs),
                       tasa_cambio: this.tasaBs
                   })
               });
               const data = await res.json();
               if (data.status === 'success') {
                   alert(`¡Pago Aprobado! Referencia Bancaria: ${data.referencia_bancaria}. Su compra ha sido confirmada.`);
                   window.location.href = '/tienda/pedido-exitoso';
               } else {
                   alert('Rechazo Bancario: ' + data.message);
               }

           } catch (e) {
               alert('Error de conexión: ' + e.message);
           } finally {
               this.procesando = false;
           }
       }
   }
}
</script>
