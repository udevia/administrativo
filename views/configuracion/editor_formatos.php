<?php
$pageTitle = 'Diseñador de Formatos de Impresión - mi';
$activeMenu = 'formatos';
ob_start();
?>
<div class="space-y-4" x-data="editorFormatosApp()">
    <!-- Barra superior -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-code text-blue-600"></i> Diseñador de Formatos de Impresión
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Personaliza etiquetas HTML/CSS para tickets y documentos con inyección de licencia</p>
        </div>
        <div class="flex items-center gap-3">
            <select x-model="formatoSeleccionadoId" @change="cargarFormato()" class="text-xs font-semibold border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                <option value="1">🧾 Factura Fiscal (Ticket 80mm)</option>
                <option value="2">📄 Nota de Entrega (Carta)</option>
                <option value="3">📋 Presupuesto (Carta)</option>
                <option value="4">📦 Pedido de Almacén (Ticket)</option>
            </select>
            <button @click="guardarPlantilla()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Plantilla
            </button>
        </div>
    </div>

    <!-- Área de Trabajo Dividida -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- Panel Izquierdo: Etiquetas Disponibles (3 cols) -->
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-col space-y-3">
            <h3 class="font-bold text-xs uppercase text-slate-400 tracking-wider">Etiquetas Dinámicas</h3>
            
            <div class="space-y-3 text-xs">
                <div>
                    <span class="font-bold text-slate-700 block mb-1">Empresa (Licencia):</span>
                    <button @click="insertarEtiqueta('{{empresa_nombre}}')" class="w-full text-left font-mono bg-blue-50 text-blue-800 p-1.5 rounded-lg mb-1 hover:bg-blue-100 text-[11px] font-bold">{{empresa_nombre}}</button>
                    <button @click="insertarEtiqueta('{{empresa_rif}}')" class="w-full text-left font-mono bg-blue-50 text-blue-800 p-1.5 rounded-lg hover:bg-blue-100 text-[11px] font-bold">{{empresa_rif}}</button>
                </div>
                <div>
                    <span class="font-bold text-slate-700 block mb-1">Documento:</span>
                    <button @click="insertarEtiqueta('{{numero_documento}}')" class="w-full text-left font-mono bg-slate-100 text-slate-700 p-1.5 rounded-lg mb-1 hover:bg-slate-200 text-[11px]">{{numero_documento}}</button>
                    <button @click="insertarEtiqueta('{{control_fiscal}}')" class="w-full text-left font-mono bg-slate-100 text-slate-700 p-1.5 rounded-lg mb-1 hover:bg-slate-200 text-[11px]">{{control_fiscal}}</button>
                    <button @click="insertarEtiqueta('{{fecha_emision}}')" class="w-full text-left font-mono bg-slate-100 text-slate-700 p-1.5 rounded-lg hover:bg-slate-200 text-[11px]">{{fecha_emision}}</button>
                </div>
                <div>
                    <span class="font-bold text-slate-700 block mb-1">Cliente:</span>
                    <button @click="insertarEtiqueta('{{cliente_nombre}}')" class="w-full text-left font-mono bg-slate-100 text-slate-700 p-1.5 rounded-lg mb-1 hover:bg-slate-200 text-[11px]">{{cliente_nombre}}</button>
                    <button @click="insertarEtiqueta('{{cliente_rif}}')" class="w-full text-left font-mono bg-slate-100 text-slate-700 p-1.5 rounded-lg hover:bg-slate-200 text-[11px]">{{cliente_rif}}</button>
                </div>
                <div>
                    <span class="font-bold text-slate-700 block mb-1">Totales:</span>
                    <button @click="insertarEtiqueta('{{tabla_items}}')" class="w-full text-left font-mono bg-emerald-50 text-emerald-800 p-1.5 rounded-lg mb-1 hover:bg-emerald-100 text-[11px] font-bold">{{tabla_items}}</button>
                    <button @click="insertarEtiqueta('{{total_general}}')" class="w-full text-left font-mono bg-slate-100 text-slate-700 p-1.5 rounded-lg mb-1 hover:bg-slate-200 text-[11px]">{{total_general}}</button>
                    <button @click="insertarEtiqueta('{{total_general_bs}}')" class="w-full text-left font-mono bg-slate-100 text-slate-700 p-1.5 rounded-lg hover:bg-slate-200 text-[11px]">{{total_general_bs}}</button>
                </div>
            </div>
        </div>

        <!-- Panel Central: Editor de Código HTML (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden min-h-[500px]">
            <div class="p-3 bg-slate-900 text-slate-300 border-b border-slate-800 flex justify-between items-center text-xs font-mono">
                <span>Editor Plantilla HTML</span>
            </div>
            <textarea 
                id="editorArea"
                x-model="formatoActual.cuerpo_html" 
                @input.debounce.300ms="actualizarPreview()"
                class="flex-1 p-4 font-mono text-xs bg-slate-950 text-emerald-400 focus:outline-none resize-none leading-relaxed min-h-[450px]"
                spellcheck="false">
            </textarea>
        </div>

        <!-- Panel Derecho: Vista Previa en Vivo (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden min-h-[500px]">
            <div class="p-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center text-xs font-bold text-slate-700">
                <span>Vista Previa Renderizada</span>
                <button @click="actualizarPreview()" class="text-blue-600 hover:text-blue-800"><i class="fa-solid fa-rotate"></i></button>
            </div>
            <div class="flex-1 p-4 bg-slate-100 flex justify-center items-start overflow-y-auto">
                <div class="bg-white shadow-lg p-3 rounded-lg border border-slate-200 w-full min-h-[420px]">
                    <iframe id="previewFrame" class="w-full h-full border-0 min-h-[400px]"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editorFormatosApp() {
    return {
        formatoSeleccionadoId: 1,
        formatoActual: {
            id: 1,
            ancho_papel_mm: 80,
            cuerpo_html: `<!DOCTYPE html><html><head><meta charset="utf-8"><style>body{font-family:monospace;font-size:11px;width:280px;margin:0;padding:5px;}.center{text-align:center;}.right{text-align:right;}.bold{font-weight:bold;}.line{border-bottom:1px dashed #000;margin:5px 0;}table{width:100%;font-size:11px;}</style></head><body><div class="center"><span class="bold">{{empresa_nombre}}</span><br>RIF: {{empresa_rif}}<br>Factura N°: {{numero_documento}}</div><div class="line"></div><div>Fecha: {{fecha_emision}}<br>Cliente: {{cliente_nombre}} ({{cliente_rif}})</div><div class="line"></div><table><tr><td>Descripción</td><td class="right">Total</td></tr>{{tabla_items}}</table><div class="line"></div><table><tr><td>TOTAL $:</td><td class="right">{{total_general}}</td></tr><tr><td>TOTAL Bs:</td><td class="right">{{total_general_bs}}</td></tr></table><div class="center" style="margin-top:8px;">{{qr_fiscal_img}}<br>¡Gracias por su compra!</div></body></html>`
        },
        init() {
            this.actualizarPreview();
        },
        insertarEtiqueta(etiqueta) {
            const textarea = document.getElementById('editorArea');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const texto = this.formatoActual.cuerpo_html;
            this.formatoActual.cuerpo_html = texto.substring(0, start) + etiqueta + texto.substring(end);
            this.actualizarPreview();
        },
        actualizarPreview() {
            const frame = document.getElementById('previewFrame');
            if (frame && frame.contentWindow) {
                let html = this.formatoActual.cuerpo_html;
                html = html.replace(/\{\{empresa_nombre\}\}/g, 'DISTRIBUIDORA CENTRAL CARABOBO C.A.')
                           .replace(/\{\{empresa_rif\}\}/g, 'J-12345678-9')
                           .replace(/\{\{numero_documento\}\}/g, 'FAC-00012480')
                           .replace(/\{\{control_fiscal\}\}/g, '00-004589')
                           .replace(/\{\{fecha_emision\}\}/g, new Date().toISOString().split('T')[0])
                           .replace(/\{\{cliente_nombre\}\}/g, 'INVERSIONES ABC C.A.')
                           .replace(/\{\{cliente_rif\}\}/g, 'J-98765432-1')
                           .replace(/\{\{tabla_items\}\}/g, '<tr><td>HARINA PAN 1KG x 2</td><td style="text-align:right">$2.40</td></tr><tr><td>ACEITE MAIZ 1L x 1</td><td style="text-align:right">$3.50</td></tr>')
                           .replace(/\{\{subtotal\}\}/g, '$5.90')
                           .replace(/\{\{total_general\}\}/g, '$5.90')
                           .replace(/\{\{total_general_bs\}\}/g, '215.35 Bs')
                           .replace(/\{\{qr_fiscal_img\}\}/g, '<b>[QR FISCAL SENIAT]</b>');

                const doc = frame.contentWindow.document;
                doc.open();
                doc.write(html);
                doc.close();
            }
        },
        guardarPlantilla() {
            alert('Plantilla guardada exitosamente.');
        }
    }
}
</script>
<?php
$slot = ob_get_clean();
require __DIR__ . '/../layout.php';
?>