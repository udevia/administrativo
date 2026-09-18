<!-- Componente en el POS: Facturación de Pedidos Web Validados -->
<div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm" x-data="pedidosWebPosApp()">
    <div class="flex justify-between items-center mb-3">
        <h3 class="font-bold text-xs uppercase text-slate-700 flex items-center gap-2">
            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span>
            Pedidos Web Validados (Listos para Facturar)
        </h3>
        <button @click="cargarPedidosConfirmados()" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
            <i class="fa-solid fa-rotate"></i>
        </button>
    </div>
    <div class="space-y-2">
        <template x-for="p in pedidos" :key="p.id">
            <div class="p-3 bg-emerald-50/50 border border-emerald-200 rounded-xl flex justify-between items-center">
                <div>
                    <span class="font-mono font-bold text-xs text-emerald-900" x-text="p.numero_orden_web"></span>
                    <p class="text-xs font-medium text-slate-800" x-text="p.cliente_nombre"></p>
                    <span class="text-[11px] text-slate-500 font-mono" x-text="'Ref: ' + p.referencia_pago + ' | $' + Number(p.total_general).toFixed(2)"></span>
                </div>
                <button @click="generarFacturaFiscal(p)" :disabled="procesandoId !== null" class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-receipt" x-show="procesandoId !== p.id"></i>
                    <i class="fa-solid fa-spinner fa-spin" x-show="procesandoId === p.id"></i>
                    <span x-text="procesandoId === p.id ? 'Emitiendo...' : 'Emitir Factura'"></span>
                </button>
            </div>
        </template>
        <div x-show="pedidos.length === 0" class="text-center py-4 text-slate-400 text-xs font-sans">
            No hay pedidos web pendientes por facturar.
        </div>
    </div>
</div>
<script>
function pedidosWebPosApp() {
    return {
        pedidos: [],
        cargando: false,
        procesandoId: null,
        init() {
            this.cargarPedidosConfirmados();
        },
        async cargarPedidosConfirmados() {
            this.cargando = true;
            try {
                const res = await fetch('/api/ecommerce/pedidos-por-facturar');
                const json = await res.json();
                this.pedidos = json.data || [];
                window.dispatchEvent(new CustomEvent('pedidos-web-cargados', { detail: this.pedidos.length }));
            } catch (e) {
                this.pedidos = [];
            } finally {
                this.cargando = false;
            }
        },
        async generarFacturaFiscal(p) {
            if (!confirm(`¿Emitir FACTURA FISCAL para el pedido web ${p.numero_orden_web}?\nCliente: ${p.cliente_nombre}\nTotal: $${Number(p.monto_total_usd).toFixed(2)}\n\nSe descontará el stock reservado y se registrará en kardex.`)) return;
            this.procesandoId = p.id;
            try {
                const res = await fetch('/api/ecommerce/facturar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: p.id })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(`✅ ${data.message}\nFactura: ${data.numero_factura || 'N/D'} | Total: $${Number(data.total_usd || 0).toFixed(2)}`);
                    if (data.venta_id && confirm('¿Desea imprimir la factura fiscal emitida?')) {
                        window.open(`/api/ventas/${data.venta_id}/documento`, '_blank');
                    }
                    this.cargarPedidosConfirmados();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo emitir la factura.'));
                }
            } catch (e) {
                alert('Error de conexión: ' + e.message);
            } finally {
                this.procesandoId = null;
            }
        }
    }
}
</script>