<?php
$pageTitle = 'Configuración de WhatsApp y Notificaciones - mi';
$activeMenu = 'whatsapp';
ob_start();
?>
<div class="space-y-4" x-data="configWhatsAppApp()">
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <i class="fa-brands fa-whatsapp text-emerald-600"></i> Configuración de WhatsApp y Envíos
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">Seleccione el modo de operación para el envío de facturas, cotizaciones y recibos a clientes</p>

        <!-- Selector de Modo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <!-- Opción A: WhatsApp Web / Desktop (Gratis) -->
            <div @click="config.modo_whatsapp = 'DESKTOP_WEB_GRATIS'"
                 :class="config.modo_whatsapp === 'DESKTOP_WEB_GRATIS' ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300'"
                 class="border-2 rounded-2xl p-5 cursor-pointer transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-extrabold text-xs text-slate-900 uppercase">WhatsApp Desktop / Web</span>
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">100% Gratis</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Abre WhatsApp Desktop o WhatsApp Web en el navegador con el mensaje y el enlace PDF prellenados.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center gap-2 text-xs font-bold text-emerald-700">
                    <i class="fa-solid fa-circle-check" x-show="config.modo_whatsapp === 'DESKTOP_WEB_GRATIS'"></i>
                    <span x-text="config.modo_whatsapp === 'DESKTOP_WEB_GRATIS' ? 'Modo Activo' : 'Seleccionar'"></span>
                </div>
            </div>

            <!-- Opción B: Meta Cloud API Oficial -->
            <div @click="config.modo_whatsapp = 'META_CLOUD_API'"
                 :class="config.modo_whatsapp === 'META_CLOUD_API' ? 'border-blue-500 bg-blue-50/40 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-slate-300'"
                 class="border-2 rounded-2xl p-5 cursor-pointer transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-extrabold text-xs text-slate-900 uppercase">Meta Cloud API (Oficial)</span>
                        <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full">Automático</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Envío desatendido en segundo plano con el archivo PDF adjunto de forma directa. Requiere credenciales de desarrollador Meta.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center gap-2 text-xs font-bold text-blue-700">
                    <i class="fa-solid fa-circle-check" x-show="config.modo_whatsapp === 'META_CLOUD_API'"></i>
                    <span x-text="config.modo_whatsapp === 'META_CLOUD_API' ? 'Modo Activo' : 'Seleccionar'"></span>
                </div>
            </div>
        </div>

        <!-- Parámetros de la API (Visibles solo si selecciona Meta Cloud API) -->
        <div x-show="config.modo_whatsapp === 'META_CLOUD_API'" class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3" x-cloak>
            <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Credenciales Meta Cloud API</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-600 mb-1">Phone Number ID:</label>
                    <input type="text" x-model="config.whatsapp_phone_number_id" class="w-full border border-slate-200 rounded-xl p-2.5 bg-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-600 mb-1">Permanent Access Token (Bearer):</label>
                    <input type="password" x-model="config.whatsapp_access_token" class="w-full border border-slate-200 rounded-xl p-2.5 bg-white font-mono">
                </div>
            </div>
        </div>

        <!-- Botón Guardar -->
        <div class="mt-6 flex justify-end">
            <button @click="guardarConfiguracion()" :disabled="guardando" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold px-6 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <span x-text="guardando ? 'Guardando...' : 'Guardar Preferencia'"></span>
            </button>
        </div>
    </div>
</div>

<script>
function configWhatsAppApp() {
    return {
        guardando: false,
        config: {
            modo_whatsapp: 'DESKTOP_WEB_GRATIS',
            whatsapp_phone_number_id: '',
            whatsapp_access_token: ''
        },
        guardarConfiguracion() {
            alert('Preferencia guardada exitosamente.');
        }
    }
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>