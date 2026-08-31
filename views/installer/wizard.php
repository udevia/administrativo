<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente de Instalación - Sistema Administrativo mi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-900 text-slate-100 font-sans min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-slate-800 border border-slate-700 rounded-3xl shadow-2xl overflow-hidden" x-data="installerWizardApp()">
        <!-- Header -->
        <div class="bg-slate-950/60 p-6 border-b border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center font-black text-white text-xl shadow-lg">
                    mi
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white tracking-tight">Asistente de Instalación Inicial</h1>
                    <p class="text-xs text-slate-400">Sistema Administrativo & ERP Empresarial</p>
                </div>
            </div>
            <span class="text-xs font-mono font-bold bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full border border-blue-500/30" x-text="'Paso ' + paso + ' de 4'"></span>
        </div>

        <!-- Barra de Progreso -->
        <div class="w-full bg-slate-700 h-1.5">
            <div class="bg-blue-500 h-1.5 transition-all duration-300" :style="'width: ' + (paso * 25) + '%'"></div>
        </div>

        <!-- Contenido del Wizard -->
        <div class="p-6 md:p-8 space-y-6">
            
            <!-- PASO 1: Requerimientos del Servidor -->
            <div x-show="paso === 1" class="space-y-4" x-cloak>
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-server text-blue-400"></i> Verificación del Entorno de Ejecución
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">El instalador valida que su servidor cuente con las extensiones y permisos necesarios.</p>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center p-3 bg-slate-900/60 rounded-xl border border-slate-700">
                        <span class="font-medium">Versión de PHP (>= 8.0.0):</span>
                        <span class="font-bold font-mono px-2 py-0.5 rounded" :class="requisitos.php_version?.valido ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'" x-text="requisitos.php_version?.actual || 'Verificando...'"></span>
                    </div>

                    <template x-for="(val, ext) in (requisitos.extensiones || {})" :key="ext">
                        <div class="flex justify-between items-center p-2.5 bg-slate-900/40 rounded-xl border border-slate-700/60">
                            <span class="font-mono" x-text="'ext-' + ext"></span>
                            <span class="font-bold text-[11px] px-2 py-0.5 rounded" 
                                  :class="val ? 'text-emerald-400 bg-emerald-500/10' : (ext === 'gd' ? 'text-amber-400 bg-amber-500/10' : 'text-red-400 bg-red-500/10')" 
                                  x-text="val ? '✓ Instalada' : (ext === 'gd' ? '○ Opcional (SVG / API)' : '✗ Falta')"></span>
                        </div>
                    </template>
                </div>

                <div class="pt-4 flex justify-end">
                    <button @click="siguientePaso()" :disabled="!requisitos.todo_ok" class="bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-bold text-xs px-6 py-2.5 rounded-xl transition flex items-center gap-2">
                        <span>Siguiente: Base de Datos</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- PASO 2: Configuración de Base de Datos -->
            <div x-show="paso === 2" class="space-y-4" x-cloak>
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-database text-emerald-400"></i> Configuración de MySQL / MariaDB
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Ingrese los parámetros de conexión para crear y aprovisionar las 21 migraciones del sistema.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Servidor (Host):</label>
                        <input type="text" x-model="db.host" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Puerto:</label>
                        <input type="number" x-model.number="db.port" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Usuario MySQL:</label>
                        <input type="text" x-model="db.user" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Contraseña MySQL:</label>
                        <input type="password" x-model="db.pass" placeholder="(En blanco por defecto en XAMPP)" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-mono">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-bold text-slate-300 mb-1">Nombre de la Base de Datos:</label>
                        <input type="text" x-model="db.name" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-mono">
                    </div>
                </div>

                <div class="pt-4 flex justify-between">
                    <button @click="paso = 1" class="text-slate-400 hover:text-white text-xs font-semibold px-4 py-2">
                        Atrás
                    </button>
                    <button @click="instalarBD()" :disabled="procesando" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-6 py-2.5 rounded-xl transition flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin" x-show="procesando"></i>
                        <span x-text="procesando ? 'Aprovisionando Tablas...' : 'Conectar y Aprovisionar Tablas'"></span>
                    </button>
                </div>
            </div>

            <!-- PASO 3: Activación de Licencia -->
            <div x-show="paso === 3" class="space-y-4" x-cloak>
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-certificate text-amber-400"></i> Activación de Licencia Digital (.lic)
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Cargue el archivo de licencia emitido por el distribuidor oficial con firma digital RSA.</p>
                </div>

                <div class="border-2 border-dashed border-slate-700 rounded-2xl p-6 text-center space-y-3 bg-slate-900/30 hover:bg-slate-900/50 transition">
                    <i class="fa-solid fa-file-shield text-4xl text-blue-400"></i>
                    <div>
                        <label class="cursor-pointer bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-5 py-2.5 rounded-xl inline-block shadow">
                            <i class="fa-solid fa-upload"></i> Seleccionar Archivo .lic
                            <input type="file" @change="subirLicencia($event)" accept=".lic" class="hidden">
                        </label>
                    </div>
                    <p class="text-[11px] text-slate-400 font-mono">Formato aceptado: sistema.lic (RSA-SHA256)</p>
                </div>

                <template x-if="licenciaInfo">
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl space-y-1.5 text-xs">
                        <p class="font-bold text-emerald-400 flex items-center gap-2">
                            <i class="fa-solid fa-circle-check"></i> Licencia Validada Criptográficamente
                        </p>
                        <p class="text-slate-300"><b>Razón Social:</b> <span x-text="licenciaInfo.empresa"></span></p>
                        <p class="text-slate-300"><b>RIF:</b> <span x-text="licenciaInfo.rif"></span></p>
                        <p class="text-slate-300"><b>Tipo:</b> <span x-text="licenciaInfo.tipo_licencia"></span></p>
                    </div>
                </template>

                <div class="pt-4 flex justify-between">
                    <button @click="paso = 2" class="text-slate-400 hover:text-white text-xs font-semibold px-4 py-2">
                        Atrás
                    </button>
                    <button @click="siguientePaso()" :disabled="!licenciaInfo" class="bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-bold text-xs px-6 py-2.5 rounded-xl transition flex items-center gap-2">
                        <span>Siguiente: Usuario Administrador</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- PASO 4: Cuenta de Administrador y Finalización -->
            <div x-show="paso === 4" class="space-y-4" x-cloak>
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-user-shield text-indigo-400"></i> Creación de Usuario Administrador Principal
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Configure las credenciales de acceso inicial para ingresar al sistema.</p>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Nombre Completo:</label>
                        <input type="text" x-model="admin.nombre" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Nombre de Usuario (Login):</label>
                        <input type="text" x-model="admin.usuario" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Contraseña:</label>
                        <input type="password" x-model="admin.password" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white font-mono">
                    </div>
                </div>

                <div class="pt-4 flex justify-between">
                    <button @click="paso = 3" class="text-slate-400 hover:text-white text-xs font-semibold px-4 py-2">
                        Atrás
                    </button>
                    <button @click="finalizar()" :disabled="procesando" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-6 py-2.5 rounded-xl transition flex items-center gap-2">
                        <i class="fa-solid fa-rocket"></i>
                        <span>Finalizar Instalación y Entrar al Sistema</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
    function installerWizardApp() {
        return {
            paso: 1,
            procesando: false,
            requisitos: {},
            db: {
                host: '127.0.0.1',
                port: 3306,
                user: 'root',
                pass: '',
                name: 'a2_admin_system'
            },
            licenciaInfo: null,
            admin: {
                nombre: 'Administrador General',
                usuario: 'admin',
                password: 'admin123'
            },
            init() {
                this.verificarRequisitos();
            },
            async verificarRequisitos() {
                try {
                    const res = await fetch('/api/installer/requirements');
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.requisitos = data.data;
                    }
                } catch (e) {
                    console.error("Error verificando requerimientos:", e);
                }
            },
            siguientePaso() {
                this.paso++;
            },
            async instalarBD() {
                this.procesando = true;
                try {
                    const res = await fetch('/api/installer/database', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.db)
                    });
                    const json = await res.json();
                    if (json.status === 'success') {
                        alert(json.data.mensaje);
                        this.paso = 3;
                    } else {
                        alert("Error: " + json.message);
                    }
                } catch (e) {
                    alert("Error conectando con el servidor: " + e.message);
                } finally {
                    this.procesando = false;
                }
            },
            async subirLicencia(event) {
                const file = event.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('licencia', file);

                try {
                    const res = await fetch('/api/installer/license', {
                        method: 'POST',
                        body: formData
                    });
                    const json = await res.json();
                    if (json.status === 'success') {
                        this.licenciaInfo = json.data;
                    } else {
                        alert("Error de validación: " + json.message);
                    }
                } catch (e) {
                    alert("Error subiendo licencia: " + e.message);
                }
            },
            async finalizar() {
                this.procesando = true;
                try {
                    const res = await fetch('/api/installer/admin', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.admin)
                    });
                    const json = await res.json();
                    if (json.status === 'success') {
                        alert("¡Instalación completada exitosamente!");
                        window.location.href = '/pos';
                    } else {
                        alert("Error: " + json.message);
                    }
                } catch (e) {
                    alert("Error: " + e.message);
                } finally {
                    this.procesando = false;
                }
            }
        }
    }
    </script>
</body>
</html>