<?php
$pageTitle = 'Canales, Notificaciones y Pasarelas - mi ERP';
$activeMenu = 'canales';
ob_start();
?>
<div class="space-y-4" x-data="canalesApp()" x-cloak>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-violet-50 text-violet-700 font-bold text-xs">Configuración</span>
                    <h1 class="text-xl font-black text-slate-800 tracking-tight">Canales, Notificaciones y Pasarelas de Pago</h1>
                </div>
                <p class="text-xs text-slate-500 mt-1">Configure los canales de mensajería (WhatsApp, Telegram) y las pasarelas bancarias para automatizar cobros y notificaciones.</p>
            </div>
            <button @click="guardarTodo()" :disabled="guardando" class="bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-floppy-disk" x-show="!guardando"></i>
                <i class="fa-solid fa-spinner fa-spin" x-show="guardando"></i>
                <span x-text="guardando ? 'Guardando...' : 'Guardar Toda la Configuración'"></span>
            </button>
        </div>
    </div>

    <!-- Pestañas -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="border-b border-slate-200 px-5 flex gap-0 overflow-x-auto">
            <button @click="tab='whatsapp'" class="py-3 px-5 text-xs font-bold border-b-2 whitespace-nowrap transition"
                    :class="tab==='whatsapp' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/30' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-brands fa-whatsapp mr-1.5 text-emerald-600"></i>WhatsApp
            </button>
            <button @click="tab='telegram'" class="py-3 px-5 text-xs font-bold border-b-2 whitespace-nowrap transition"
                    :class="tab==='telegram' ? 'border-sky-500 text-sky-700 bg-sky-50/30' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-brands fa-telegram mr-1.5 text-sky-500"></i>Telegram Bot
            </button>
            <button @click="tab='pasarelas'" class="py-3 px-5 text-xs font-bold border-b-2 whitespace-nowrap transition"
                    :class="tab==='pasarelas' ? 'border-blue-600 text-blue-700 bg-blue-50/30' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-solid fa-university mr-1.5 text-blue-600"></i>Pasarelas Bancarias
            </button>
            <button @click="tab='notificaciones'" class="py-3 px-5 text-xs font-bold border-b-2 whitespace-nowrap transition"
                    :class="tab==='notificaciones' ? 'border-violet-600 text-violet-700 bg-violet-50/30' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <i class="fa-solid fa-bell mr-1.5 text-violet-600"></i>Reglas de Notificación
            </button>
        </div>

        <!-- ======================== WHATSAPP ======================== -->
        <div x-show="tab==='whatsapp'" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Opción A -->
                <div @click="config.whatsapp.modo = 'DESKTOP_WEB_GRATIS'"
                     :class="config.whatsapp.modo === 'DESKTOP_WEB_GRATIS' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/30' : 'border-slate-200 hover:border-slate-300'"
                     class="border-2 rounded-2xl p-5 cursor-pointer transition">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                                <i class="fa-brands fa-whatsapp text-emerald-600 text-xl"></i>
                            </div>
                            <div>
                                <span class="font-extrabold text-sm text-slate-900 block">WhatsApp Desktop / Web</span>
                                <span class="text-[10px] text-slate-500">Sin costo adicional</span>
                            </div>
                        </div>
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-1 rounded-full">100% Gratis</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">Abre WhatsApp Desktop o WhatsApp Web con el mensaje prellenado y el enlace al PDF de la factura. Requiere que el usuario tenga WhatsApp abierto en el equipo.</p>
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center gap-2 text-xs font-bold text-emerald-700">
                        <i class="fa-solid fa-circle-check" x-show="config.whatsapp.modo === 'DESKTOP_WEB_GRATIS'"></i>
                        <i class="fa-regular fa-circle" x-show="config.whatsapp.modo !== 'DESKTOP_WEB_GRATIS'"></i>
                        <span x-text="config.whatsapp.modo === 'DESKTOP_WEB_GRATIS' ? 'MODO ACTIVO' : 'Seleccionar este modo'"></span>
                    </div>
                </div>

                <!-- Opción B -->
                <div @click="config.whatsapp.modo = 'META_CLOUD_API'"
                     :class="config.whatsapp.modo === 'META_CLOUD_API' ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/30' : 'border-slate-200 hover:border-slate-300'"
                     class="border-2 rounded-2xl p-5 cursor-pointer transition">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                <i class="fa-brands fa-meta text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <span class="font-extrabold text-sm text-slate-900 block">Meta Cloud API (Oficial)</span>
                                <span class="text-[10px] text-slate-500">Envío automático desatendido</span>
                            </div>
                        </div>
                        <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2.5 py-1 rounded-full">Automático</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">Envío desatendido en segundo plano con el PDF adjunto de forma directa al cliente. Requiere número de teléfono verificado en Meta Business y credenciales de la API.</p>
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center gap-2 text-xs font-bold text-blue-700">
                        <i class="fa-solid fa-circle-check" x-show="config.whatsapp.modo === 'META_CLOUD_API'"></i>
                        <i class="fa-regular fa-circle" x-show="config.whatsapp.modo !== 'META_CLOUD_API'"></i>
                        <span x-text="config.whatsapp.modo === 'META_CLOUD_API' ? 'MODO ACTIVO' : 'Seleccionar este modo'"></span>
                    </div>
                </div>
            </div>

            <!-- Credenciales Meta API -->
            <div x-show="config.whatsapp.modo === 'META_CLOUD_API'" class="bg-slate-50 border border-slate-200 rounded-2xl p-5 space-y-4 text-xs">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-key text-blue-600"></i> Credenciales Meta Cloud API
                    <a href="https://developers.facebook.com/docs/whatsapp/cloud-api" target="_blank" class="ml-auto text-blue-600 hover:underline font-normal">Ver documentación ↗</a>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Phone Number ID *</label>
                        <input type="text" x-model="config.whatsapp.phone_number_id" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="Ej: 123456789012345">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">WhatsApp Business Account ID</label>
                        <input type="text" x-model="config.whatsapp.waba_id" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="Ej: 987654321098765">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Permanent Access Token (Bearer) *</label>
                        <div class="relative">
                            <input :type="showToken ? 'text' : 'password'" x-model="config.whatsapp.access_token" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-mono pr-10 focus:outline-none focus:border-blue-500" placeholder="EAAxxxxxxxx...">
                            <button type="button" @click="showToken = !showToken" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                                <i :class="showToken ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Template de Factura (nombre)</label>
                        <input type="text" x-model="config.whatsapp.template_factura" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-blue-500" placeholder="envio_factura">
                    </div>
                    <div class="flex items-end">
                        <button type="button" @click="probarWhatsApp()" :disabled="probando" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-paper-plane" x-show="!probando"></i>
                            <i class="fa-solid fa-spinner fa-spin" x-show="probando"></i>
                            <span x-text="probando ? 'Enviando...' : 'Enviar Mensaje de Prueba'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================== TELEGRAM ======================== -->
        <div x-show="tab==='telegram'" class="p-6 space-y-6">
            <div class="bg-sky-50 border border-sky-200 rounded-2xl p-5 flex gap-4">
                <div class="w-12 h-12 rounded-xl bg-sky-500 flex items-center justify-center text-white text-2xl flex-shrink-0">
                    <i class="fa-brands fa-telegram"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sky-900">Integración con Telegram Bot</h3>
                    <p class="text-xs text-sky-700 mt-1">Vincule un bot de Telegram para recibir alertas automáticas de ventas, alertas de inventario bajo y notificaciones de caja en tiempo real.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
                <div class="space-y-3">
                    <h4 class="font-bold text-slate-700">Paso 1: Crear el Bot en BotFather</h4>
                    <ol class="text-slate-600 space-y-1.5 list-decimal list-inside">
                        <li>Abra Telegram y busque <strong>@BotFather</strong></li>
                        <li>Envíe el comando <code class="bg-slate-100 px-1 rounded">/newbot</code></li>
                        <li>Elija un nombre y username para su bot</li>
                        <li>BotFather le entregará el <strong>Token de API</strong></li>
                    </ol>
                </div>
                <div class="space-y-3">
                    <h4 class="font-bold text-slate-700">Paso 2: Obtener el Chat ID</h4>
                    <ol class="text-slate-600 space-y-1.5 list-decimal list-inside">
                        <li>Inicie una conversación con su bot</li>
                        <li>Envíe cualquier mensaje al bot</li>
                        <li>Visite <code class="bg-slate-100 px-1 rounded">api.telegram.org/bot{TOKEN}/getUpdates</code></li>
                        <li>El chat ID aparece en el campo <code class="bg-slate-100 px-1 rounded">chat.id</code></li>
                    </ol>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Bot API Token *</label>
                    <div class="relative">
                        <input :type="showTgToken ? 'text' : 'password'" x-model="config.telegram.bot_token" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-sky-500 pr-10" placeholder="1234567890:AAF...">
                        <button type="button" @click="showTgToken = !showTgToken" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <i :class="showTgToken ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Chat ID (Grupo o Usuario) *</label>
                    <input type="text" x-model="config.telegram.chat_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono focus:outline-none focus:border-sky-500" placeholder="Ej: -1001234567890 (grupo) o 123456789 (usuario)">
                </div>

                <!-- Webhook URL -->
                <div class="md:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">URL del Webhook (generado automáticamente)</label>
                    <div class="flex gap-2">
                        <input type="text" readonly :value="webhookUrl" class="flex-1 bg-slate-100 border border-slate-200 rounded-xl p-2.5 font-mono text-slate-600 cursor-not-allowed text-[11px]">
                        <button type="button" @click="copiarWebhook()" class="bg-slate-200 hover:bg-slate-300 px-4 py-2.5 rounded-xl font-bold text-slate-700 transition">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        <button type="button" @click="registrarWebhook()" :disabled="!config.telegram.bot_token" class="bg-sky-600 hover:bg-sky-700 text-white font-bold px-4 py-2.5 rounded-xl transition disabled:opacity-50">
                            Registrar Webhook
                        </button>
                    </div>
                </div>

                <!-- Probar conexión -->
                <div class="md:col-span-2 flex gap-3 items-center">
                    <button type="button" @click="probarTelegram()" :disabled="!config.telegram.bot_token || !config.telegram.chat_id || probandoTg"
                            class="bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-paper-plane" x-show="!probandoTg"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="probandoTg"></i>
                        <span x-text="probandoTg ? 'Enviando...' : 'Probar Conexión'"></span>
                    </button>
                    <div x-show="resultadoTg" class="flex items-center gap-2 text-xs font-bold"
                         :class="resultadoTg === 'ok' ? 'text-emerald-700' : 'text-red-600'">
                        <i :class="resultadoTg === 'ok' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                        <span x-text="resultadoTg === 'ok' ? '✅ Mensaje enviado correctamente. Bot activo.' : '❌ Error. Verifique Token y Chat ID.'"></span>
                    </div>
                </div>
            </div>

            <!-- Alertas a enviar -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-xs">
                <h4 class="font-bold text-slate-700 mb-3">Eventos que generan notificación automática</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <template x-for="(ev, k) in config.telegram.eventos" :key="k">
                        <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-sky-300 transition">
                            <input type="checkbox" x-model="config.telegram.eventos[k]" class="rounded text-sky-600">
                            <span class="font-semibold text-slate-700 text-[11px]" x-text="etiquetaEvento(k)"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        <!-- ======================== PASARELAS BANCARIAS ======================== -->
        <div x-show="tab==='pasarelas'" class="p-6 space-y-5">
            <!-- C2P / Pago Móvil -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                <div class="bg-slate-800 text-white px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-orange-500 flex items-center justify-center"><i class="fa-solid fa-mobile-screen-button text-sm"></i></div>
                    <div>
                        <span class="font-bold text-sm">Pago Móvil C2P (Interbancario)</span>
                        <span class="text-[10px] text-slate-400 ml-2">Débito automático vía SUICHE 7B</span>
                    </div>
                    <label class="ml-auto flex items-center gap-2 cursor-pointer" @click.stop>
                        <span class="text-xs font-bold text-slate-300">Activo</span>
                        <div class="relative">
                            <input type="checkbox" x-model="config.c2p.activo" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-600 peer-checked:bg-orange-500 rounded-full transition"></div>
                            <div class="absolute top-0.5 left-0.5 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-4 shadow"></div>
                        </div>
                    </label>
                </div>
                <div class="p-5 text-xs space-y-3" :class="!config.c2p.activo && 'opacity-40 pointer-events-none'">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Banco Origen (BIC)</label>
                            <select x-model="config.c2p.banco_bic" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold">
                                <option value="0134">0134 - Banesco</option>
                                <option value="0102">0102 - Venezuela</option>
                                <option value="0108">0108 - Provincial BBVA</option>
                                <option value="0105">0105 - Mercantil</option>
                                <option value="0175">0175 - Bicentenario</option>
                                <option value="0114">0114 - Bancaribe</option>
                                <option value="0172">0172 - Bancamiga</option>
                                <option value="0137">0137 - Banco Plaza</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">RIF de la Empresa</label>
                            <input type="text" x-model="config.c2p.rif_empresa" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono" placeholder="J123456789">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Teléfono Registrado en el Banco</label>
                            <input type="text" x-model="config.c2p.telefono" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono" placeholder="04XXXXXXXXX">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">API Key / Token del Banco</label>
                            <input type="password" x-model="config.c2p.api_key" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">URL del API Endpoint</label>
                            <input type="text" x-model="config.c2p.api_url" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono text-[10px]" placeholder="https://api.banco.com/c2p/debit">
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="probarC2P()" :disabled="!config.c2p.activo" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold px-4 py-2.5 rounded-xl transition">
                                <i class="fa-solid fa-vial mr-1"></i> Probar Conexión
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banesco Online / Empresarial -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                <div class="bg-slate-800 text-white px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center text-sm font-black">B</div>
                    <div>
                        <span class="font-bold text-sm">Banesco API Empresarial</span>
                        <span class="text-[10px] text-slate-400 ml-2">Transferencias y débito autorizados</span>
                    </div>
                    <label class="ml-auto flex items-center gap-2 cursor-pointer" @click.stop>
                        <span class="text-xs font-bold text-slate-300">Activo</span>
                        <div class="relative">
                            <input type="checkbox" x-model="config.banesco.activo" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-600 peer-checked:bg-red-600 rounded-full transition"></div>
                            <div class="absolute top-0.5 left-0.5 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-4 shadow"></div>
                        </div>
                    </label>
                </div>
                <div class="p-5 text-xs grid grid-cols-1 md:grid-cols-3 gap-3" :class="!config.banesco.activo && 'opacity-40 pointer-events-none'">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Client ID</label>
                        <input type="text" x-model="config.banesco.client_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Client Secret</label>
                        <input type="password" x-model="config.banesco.client_secret" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Cuenta Destino (Recibir Pagos)</label>
                        <input type="text" x-model="config.banesco.cuenta_destino" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono" placeholder="0134-XXXX-XXXX">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Endpoint API</label>
                        <input type="text" x-model="config.banesco.api_url" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono text-[10px]" placeholder="https://api.banesco.com/...">
                    </div>
                    <div class="flex items-end">
                        <button type="button" @click="probarBanesco()" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold px-4 py-2.5 rounded-xl transition">
                            <i class="fa-solid fa-vial mr-1"></i> Probar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Banco Plaza -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                <div class="bg-slate-800 text-white px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-700 flex items-center justify-center text-sm font-black">P</div>
                    <div>
                        <span class="font-bold text-sm">Banco Plaza — Pasarela de Pago</span>
                        <span class="text-[10px] text-slate-400 ml-2">Link de pago y QR bancario</span>
                    </div>
                    <label class="ml-auto flex items-center gap-2 cursor-pointer" @click.stop>
                        <span class="text-xs font-bold text-slate-300">Activo</span>
                        <div class="relative">
                            <input type="checkbox" x-model="config.bancoplaza.activo" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-600 peer-checked:bg-blue-600 rounded-full transition"></div>
                            <div class="absolute top-0.5 left-0.5 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-4 shadow"></div>
                        </div>
                    </label>
                </div>
                <div class="p-5 text-xs grid grid-cols-1 md:grid-cols-3 gap-3" :class="!config.bancoplaza.activo && 'opacity-40 pointer-events-none'">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Merchant ID</label>
                        <input type="text" x-model="config.bancoplaza.merchant_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">API Secret Key</label>
                        <input type="password" x-model="config.bancoplaza.api_secret" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono">
                    </div>
                    <div class="flex items-end">
                        <button type="button" @click="probarBancoPlaza()" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold px-4 py-2.5 rounded-xl transition">
                            <i class="fa-solid fa-vial mr-1"></i> Probar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================== REGLAS DE NOTIFICACIÓN ======================== -->
        <div x-show="tab==='notificaciones'" class="p-6 space-y-4 text-xs">
            <p class="text-slate-600">Configure los eventos del sistema que enviarán notificaciones automáticas y por cuál canal.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="p-3">Evento del Sistema</th>
                            <th class="p-3 text-center">WhatsApp</th>
                            <th class="p-3 text-center">Telegram</th>
                            <th class="p-3 text-center">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <template x-for="regla in config.reglas_notificacion" :key="regla.evento">
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 text-slate-800 font-semibold" x-text="regla.nombre"></td>
                                <td class="p-3 text-center">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="regla.whatsapp" class="rounded text-emerald-600">
                                    </label>
                                </td>
                                <td class="p-3 text-center">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="regla.telegram" class="rounded text-sky-600">
                                    </label>
                                </td>
                                <td class="p-3 text-center">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="regla.email" class="rounded text-violet-600">
                                    </label>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function canalesApp() {
    return {
        tab: 'whatsapp',
        guardando: false,
        probando: false,
        probandoTg: false,
        showToken: false,
        showTgToken: false,
        resultadoTg: '',

        config: {
            whatsapp: {
                modo: 'DESKTOP_WEB_GRATIS',
                phone_number_id: '',
                waba_id: '',
                access_token: '',
                template_factura: 'envio_factura'
            },
            telegram: {
                bot_token: '',
                chat_id: '',
                eventos: {
                    nueva_venta: true,
                    cierre_caja: true,
                    stock_bajo: true,
                    pago_recibido: true,
                    nueva_oc: false,
                    venta_mayor_monto: false
                }
            },
            c2p: { activo: false, banco_bic: '0134', rif_empresa: '', telefono: '', api_key: '', api_url: '' },
            banesco: { activo: false, client_id: '', client_secret: '', cuenta_destino: '', api_url: '' },
            bancoplaza: { activo: false, merchant_id: '', api_secret: '' },
            reglas_notificacion: [
                { evento: 'nueva_venta', nombre: 'Nueva Venta Registrada', whatsapp: false, telegram: true, email: false },
                { evento: 'cierre_caja', nombre: 'Cierre de Caja / Arqueo Z', whatsapp: false, telegram: true, email: true },
                { evento: 'stock_bajo', nombre: 'Producto con Stock Bajo el Mínimo', whatsapp: false, telegram: true, email: false },
                { evento: 'pago_recibido', nombre: 'Pago C2P / Transferencia Recibida', whatsapp: true, telegram: true, email: false },
                { evento: 'cliente_factura', nombre: 'Envío de Factura a Cliente', whatsapp: true, telegram: false, email: true },
                { evento: 'orden_compra', nombre: 'Orden de Compra Generada', whatsapp: false, telegram: true, email: true },
                { evento: 'vencimiento_lote', nombre: 'Lote próximo a vencer (< 30 días)', whatsapp: false, telegram: true, email: false },
                { evento: 'sat_entregado', nombre: 'Equipo SAT Listo para Retirar', whatsapp: true, telegram: false, email: false },
            ]
        },

        get webhookUrl() {
            return window.location.origin + '/api/telegram/webhook/' + (this.config.telegram.bot_token ? btoa(this.config.telegram.bot_token).substring(0, 12) : 'CONFIGURA_TOKEN');
        },

        async init() {
            try {
                const r = await fetch('/api/configuracion/canales');
                const json = await r.json();
                if (json.data) {
                    // Merge conservando estructura base
                    if (json.data.whatsapp) Object.assign(this.config.whatsapp, json.data.whatsapp);
                    if (json.data.telegram) Object.assign(this.config.telegram, json.data.telegram);
                    if (json.data.c2p) Object.assign(this.config.c2p, json.data.c2p);
                    if (json.data.banesco) Object.assign(this.config.banesco, json.data.banesco);
                    if (json.data.bancoplaza) Object.assign(this.config.bancoplaza, json.data.bancoplaza);
                }
            } catch(e) {}
        },

        async guardarTodo() {
            this.guardando = true;
            try {
                const res = await fetch('/api/configuracion/canales', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.config)
                });
                const json = await res.json();
                alert(json.status === 'success' ? '✅ Configuración guardada correctamente.' : '❌ Error: ' + json.message);
            } catch(e) { alert('Error: ' + e.message); } finally { this.guardando = false; }
        },

        async probarWhatsApp() {
            const tel = prompt('Número destino para prueba (con código país, sin +):', '584XX');
            if (!tel) return;
            this.probando = true;
            try {
                const res = await fetch('/api/configuracion/test-whatsapp', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ telefono: tel, config: this.config.whatsapp })
                });
                const json = await res.json();
                alert(json.status === 'success' ? '✅ Mensaje enviado correctamente.' : '❌ Error: ' + json.message);
            } catch(e) { alert('Error: ' + e.message); } finally { this.probando = false; }
        },

        async probarTelegram() {
            this.probandoTg = true;
            this.resultadoTg = '';
            try {
                const res = await fetch('/api/configuracion/test-telegram', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ bot_token: this.config.telegram.bot_token, chat_id: this.config.telegram.chat_id })
                });
                const json = await res.json();
                this.resultadoTg = json.status === 'success' ? 'ok' : 'error';
            } catch(e) { this.resultadoTg = 'error'; } finally { this.probandoTg = false; }
        },

        async registrarWebhook() {
            try {
                const res = await fetch('/api/configuracion/telegram-webhook', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ bot_token: this.config.telegram.bot_token, webhook_url: this.webhookUrl })
                });
                const json = await res.json();
                alert(json.status === 'success' ? '✅ Webhook registrado correctamente.' : '❌ Error: ' + json.message);
            } catch(e) { alert('Error: ' + e.message); }
        },

        copiarWebhook() {
            navigator.clipboard.writeText(this.webhookUrl);
            alert('URL copiada al portapapeles.');
        },

        async probarC2P() { alert('🔧 Función de prueba C2P en desarrollo. Configure la API URL correcta.'); },
        async probarBanesco() { alert('🔧 Función de prueba Banesco en desarrollo.'); },
        async probarBancoPlaza() { alert('🔧 Función de prueba Banco Plaza en desarrollo.'); },

        etiquetaEvento(k) {
            const labels = {
                nueva_venta: '🛒 Nueva Venta', cierre_caja: '🏦 Cierre de Caja',
                stock_bajo: '📦 Stock Bajo', pago_recibido: '💳 Pago Recibido',
                nueva_oc: '🚛 Nueva OC', venta_mayor_monto: '💰 Venta > Monto'
            };
            return labels[k] || k;
        }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
