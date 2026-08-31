<?php
$pageTitle = 'Suite de Reportes - mi ERP';
$activeMenu = 'reportes';
ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="space-y-4" x-data="reportesApp()" x-cloak>
    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs">Inteligencia de Negocios</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Suite de Reportes</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Métricas y estadísticas en tiempo real del desempeño de su empresa.</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="date" x-model="desde" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50">
            <span class="text-slate-400 text-xs">hasta</span>
            <input type="date" x-model="hasta" class="text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50">
            <button @click="cargarDatos()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-xs shadow-sm">
                <i class="fa-solid fa-arrows-rotate"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- KPIs Globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <!-- Ventas -->
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-2xl p-5 text-white shadow-sm border border-emerald-900/20 relative overflow-hidden">
            <i class="fa-solid fa-chart-line text-5xl absolute -bottom-2 -right-2 text-white/10"></i>
            <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider mb-1">Total Ventas</p>
            <h2 class="text-3xl font-black font-mono" x-text="'$' + fmt(kpis.ventas.monto_total)"></h2>
            <p class="text-xs text-emerald-100 mt-2 font-medium" x-text="kpis.ventas.total_transacciones + ' transacciones exitosas'"></p>
        </div>
        <!-- CxC -->
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white shadow-sm border border-amber-700/20 relative overflow-hidden">
            <i class="fa-solid fa-hand-holding-dollar text-5xl absolute -bottom-2 -right-2 text-white/10"></i>
            <p class="text-amber-100 text-xs font-bold uppercase tracking-wider mb-1">Cuentas por Cobrar</p>
            <h2 class="text-3xl font-black font-mono" x-text="'$' + fmt(kpis.ventas.saldo_por_cobrar)"></h2>
            <p class="text-xs text-amber-100 mt-2 font-medium">Deuda pendiente de clientes</p>
        </div>
        <!-- Compras -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Compras y Gastos</p>
            <h2 class="text-3xl font-black font-mono text-slate-800" x-text="'$' + fmt(kpis.compras.monto_total)"></h2>
            <p class="text-xs text-slate-500 mt-2 font-medium" x-text="kpis.compras.total_compras + ' órdenes procesadas'"></p>
        </div>
        <!-- Inventario -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Valorización Inventario</p>
            <h2 class="text-3xl font-black font-mono text-indigo-700" x-text="'$' + fmt(kpis.inventario.valor_total)"></h2>
            <p class="text-xs text-slate-500 mt-2 font-medium" x-text="'PVP Potencial: $' + fmt(kpis.inventario.pvp_total)"></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Gráfico de Ventas -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-chart-area text-blue-600"></i> Evolución de Ventas (Diaria)
            </h3>
            <div class="h-72 w-full relative">
                <canvas id="chartVentas"></canvas>
            </div>
        </div>

        <!-- Top Productos -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-trophy text-amber-500"></i> Top Productos Vendidos
            </h3>
            <div class="space-y-3">
                <template x-for="(prod, i) in topProductos" :key="prod.codigo">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs"
                             :class="i === 0 ? 'bg-amber-100 text-amber-700' : (i === 1 ? 'bg-slate-200 text-slate-600' : (i === 2 ? 'bg-orange-100 text-orange-700' : 'bg-slate-50 text-slate-400'))">
                            <span x-text="i+1"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate" x-text="prod.descripcion"></p>
                            <p class="text-[10px] text-slate-500 font-mono" x-text="prod.cantidad_vendida + ' unid. vendidas'"></p>
                        </div>
                        <div class="font-black text-xs font-mono text-emerald-700" x-text="'$' + fmt(prod.monto_generado)"></div>
                    </div>
                </template>
                <div x-show="topProductos.length === 0" class="text-center py-8 text-slate-400 text-xs">
                    No hay ventas registradas en este período.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function reportesApp() {
    return {
        desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        hasta: new Date().toISOString().split('T')[0],
        kpis: {
            ventas: { total_transacciones: 0, monto_total: 0, saldo_por_cobrar: 0 },
            compras: { total_compras: 0, monto_total: 0 },
            inventario: { valor_total: 0, pvp_total: 0 }
        },
        topProductos: [],
        chartInstance: null,

        async init() {
            await this.cargarDatos();
        },

        async cargarDatos() {
            try {
                // Paralelizamos peticiones
                const [rVentas, rCompras, rInv, rDiario, rTop] = await Promise.all([
                    fetch(`/api/reportes/ventas_resumen?desde=${this.desde}&hasta=${this.hasta}`).then(r=>r.json()),
                    fetch(`/api/reportes/compras_resumen?desde=${this.desde}&hasta=${this.hasta}`).then(r=>r.json()),
                    fetch(`/api/reportes/inventario_valorizacion`).then(r=>r.json()),
                    fetch(`/api/reportes/ventas_diarias?desde=${this.desde}&hasta=${this.hasta}`).then(r=>r.json()),
                    fetch(`/api/reportes/top_productos?desde=${this.desde}&hasta=${this.hasta}`).then(r=>r.json())
                ]);

                if(rVentas.status === 'success') this.kpis.ventas = rVentas.data;
                if(rCompras.status === 'success') this.kpis.compras = rCompras.data;
                if(rInv.status === 'success') this.kpis.inventario = rInv.data;
                if(rTop.status === 'success') this.topProductos = rTop.data;

                if(rDiario.status === 'success') {
                    this.renderizarGrafico(rDiario.data);
                }
            } catch(e) {
                console.error("Error cargando reportes", e);
            }
        },

        renderizarGrafico(data) {
            const ctx = document.getElementById('chartVentas');
            if(!ctx) return;
            
            if(this.chartInstance) {
                this.chartInstance.destroy();
            }

            const labels = data.map(d => d.fecha);
            const values = data.map(d => Number(d.total));

            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas USD',
                        data: values,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#2563eb',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: {
                                callback: function(value) { return '$' + value; },
                                color: '#64748b', font: { family: 'monospace', size: 10 }
                            }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { color: '#64748b', font: { size: 10 } }
                        }
                    }
                }
            });
        },

        fmt(val) { return Number(val||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    };
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
