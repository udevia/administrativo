<?php
/**
 * views/layout.php - Layout Maestro del Sistema Administrativo mi
 */
$pageTitle = $pageTitle ?? 'Sistema Administrativo mi';
$activeMenu = $activeMenu ?? 'pos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Barra Superior Global -->
        <header class="bg-slate-900 text-white shadow-md z-30 sticky top-0">
            <div class="max-w-full mx-auto px-4 py-2.5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <a href="/pos" class="flex items-center gap-2 group">
                        <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center font-black text-white text-lg shadow-inner group-hover:scale-105 transition">
                            mi
                        </div>
                        <div>
                            <span class="font-extrabold tracking-tight text-white text-base">ADMINISTRATIVO</span>
                            <span class="text-[10px] uppercase font-mono block text-blue-400 -mt-1 font-semibold">ERP Enterprise Edition</span>
                        </div>
                    </a>
                </div>

                <!-- Tasa de Cambio y Estado Fiscal -->
                <div class="flex items-center gap-4 text-xs">
                    <?php $tasaOficialUsd = \App\Core\Database::getTasaActualUsd(); ?>
                    <div class="hidden sm:flex items-center gap-2 bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-lg">
                        <span class="text-slate-400 font-medium">Tasa Oficial:</span>
                        <span class="font-mono font-bold text-emerald-400">1.00 $ = <?= number_format($tasaOficialUsd, 2, ',', '.') ?> VES</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Licencia Activa
                        </span>
                    </div>

                    <div class="flex items-center gap-2 pl-2 border-l border-slate-700">
                        <div class="w-7 h-7 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <span class="font-medium hidden md:inline">Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenedor Principal: Sidebar + Contenido -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Menú Lateral -->
            <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col justify-between shrink-0 border-r border-slate-800 hidden lg:flex">
                <div class="p-3 space-y-1 overflow-y-auto">
                    <div class="text-[10px] font-bold text-slate-400 uppercase px-3 py-1.5 tracking-wider">Ventas</div>
                    <a href="/pos" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'pos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-cash-register w-4 text-center"></i>
                        <span>POS / Venta Rápida</span>
                    </a>
                    <a href="/ventas/facturacion" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'ventas_facturacion') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-file-invoice-dollar w-4 text-center"></i>
                        <span>Facturas</span>
                    </a>
                    <a href="/ventas/devoluciones" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'ventas_devoluciones') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-rotate-left w-4 text-center"></i>
                        <span>Devoluciones / N.Crédito</span>
                    </a>
                    <a href="/ventas/pedidos" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'ventas_pedidos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-cart-flatbed w-4 text-center"></i>
                        <span>Pedidos / Apartados</span>
                    </a>
                    <a href="/ventas/presupuestos" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'ventas_presupuestos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-file-lines w-4 text-center"></i>
                        <span>Presupuestos</span>
                    </a>

                    <div class="text-[10px] font-bold text-slate-400 uppercase px-3 py-1.5 mt-2 tracking-wider">Compras</div>
                    <a href="/compras/orden" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'compras_orden') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-file-circle-plus w-4 text-center"></i>
                        <span>Orden de Compra</span>
                    </a>
                    <a href="/compras/recepcion" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'compras_recepcion') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-truck-ramp-box w-4 text-center"></i>
                        <span>Recepción de Mercancía</span>
                    </a>
                    <a href="/compras/nueva" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'compras_nueva') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-receipt w-4 text-center"></i>
                        <span>Ingresar Factura Compra</span>
                    </a>
                    <a href="/compras/sugerencias" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'compras_sugerencias') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-lightbulb w-4 text-center"></i>
                        <span>Sugerencias de Compra</span>
                    </a>

                    <div class="text-[10px] font-bold text-slate-400 uppercase px-3 py-1.5 mt-2 tracking-wider">Operaciones</div>
                    <a href="/sat/panel" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'sat') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-screwdriver-wrench w-4 text-center"></i>
                        <span>Taller / SAT</span>
                    </a>
                    <a href="/produccion/panel" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'produccion') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-industry w-4 text-center"></i>
                        <span>Producción (BOM)</span>
                    </a>


                    <div class="text-[10px] font-bold text-slate-400 uppercase px-3 py-1.5 mt-3 tracking-wider flex items-center justify-between">
                        <span>Archivos Maestros</span>
                        <a href="/maestros" class="text-blue-400 hover:text-blue-300 text-[10px] font-bold">Ver Todo</a>
                    </div>
                    <a href="/maestros/productos" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_productos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-boxes-stacked w-4 text-center"></i>
                        <span>Catálogo de Productos</span>
                    </a>
                    <a href="/maestros/clientes" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_clientes') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-address-book w-4 text-center"></i>
                        <span>Ficha de Clientes</span>
                    </a>
                    <a href="/maestros/proveedores" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_proveedores') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-truck-field w-4 text-center"></i>
                        <span>Ficha de Proveedores</span>
                    </a>
                    <a href="/maestros/departamentos" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_departamentos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-sitemap w-4 text-center"></i>
                        <span>Deptos y Categorías</span>
                    </a>
                    <a href="/maestros/vendedores" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_vendedores') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-user-tag w-4 text-center"></i>
                        <span>Vendedores & Comisiones</span>
                    </a>
                    <a href="/maestros/zonas" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_zonas') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-map-location-dot w-4 text-center"></i>
                        <span>Zonas y Rutas</span>
                    </a>
                    <a href="/maestros/monedas" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_monedas') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-coins w-4 text-center"></i>
                        <span>Monedas & Tasas</span>
                    </a>
                    <a href="/maestros/depositos" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_depositos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-warehouse w-4 text-center"></i>
                        <span>Depósitos y Almacenes</span>
                    </a>
                    <a href="/maestros/bancos" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'maestros_bancos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-building-columns w-4 text-center"></i>
                        <span>Bancos y Cajas</span>
                    </a>

                    <div class="text-[10px] font-bold text-slate-400 uppercase px-3 py-1.5 mt-3 tracking-wider">Finanzas y Nómina</div>
                    <a href="/contabilidad/panel" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'contabilidad') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-book-journal-whills w-4 text-center"></i>
                        <span>Contabilidad Integrada</span>
                    </a>
                    <a href="/contador/multicliente" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'contador') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-user-tie w-4 text-center"></i>
                        <span>Panel del Contador</span>
                    </a>
                    <a href="/nomina/panel" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'nomina') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-users-gear w-4 text-center"></i>
                        <span>Nómina y Personal</span>
                    </a>
                    <a href="/activos/panel" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'activos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-boxes-packing w-4 text-center"></i>
                        <span>Activos Fijos</span>
                    </a>

                    <div class="text-[10px] font-bold text-slate-400 uppercase px-3 py-1.5 mt-3 tracking-wider">Informes & Configuración</div>
                    <a href="/reportes/suite" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'reportes') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                        <span>Suite de Reportes mi</span>
                    </a>
                    <a href="/configuracion/formatos" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'formatos') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-file-invoice w-4 text-center"></i>
                        <span>Editor de Formatos</span>
                    </a>
                    <a href="/configuracion/migracion" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'migracion') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-file-import w-4 text-center"></i>
                        <span>Migración Masiva CSV</span>
                    </a>
                    <a href="/configuracion/whatsapp" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'whatsapp') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-brands fa-whatsapp w-4 text-center"></i>
                        <span>WhatsApp & Notificaciones</span>
                    </a>
                    <a href="/auditoria/logs" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white transition <?= ($activeMenu === 'auditoria') ? 'bg-blue-600 text-white' : '' ?>">
                        <i class="fa-solid fa-shield-halved w-4 text-center"></i>
                        <span>Visor de Auditoría</span>
                    </a>
                    <a href="/tienda" target="_blank" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 text-blue-400 hover:text-blue-300 transition">
                        <i class="fa-solid fa-store w-4 text-center"></i>
                        <span>Tienda Online Oficial <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></span>
                    </a>
                </div>
                <div class="border-t border-slate-800 p-3 flex items-center justify-between gap-2">
                    <div class="min-w-0 text-[11px] text-slate-400">
                        <div class="truncate font-semibold text-slate-200"><?= htmlspecialchars((string)(\App\Core\Session::user()['nombre'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="truncate">Sesión activa</div>
                    </div>
                    <a href="/logout" class="rounded-lg px-2 py-1 text-[11px] font-bold text-red-300 hover:bg-red-500/10" title="Cerrar sesión">Salir</a>
                </div>
                <div class="p-3 border-t border-slate-800 text-[11px] text-slate-400 font-mono text-center">
                    mi v2.0 Enterprise
                </div>
            </aside>

            <!-- Área de Trabajo Central -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 space-y-4">
                <?= $slot ?? '' ?>
            </main>
        </div>
    </div>
</body>
</html>
