<!-- Modal del POS: Asignación de Seriales por Renglón -->
<div x-show="modalSerialesAbierto" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-5 space-y-4">
        
        <div class="border-b pb-3 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-barcode text-blue-600"></i> Escaneo de Seriales / IMEI
                </h3>
                <p class="text-xs text-slate-500 font-medium" x-text="productoSerialActual.descripcion"></p>
            </div>
            <span class="text-xs font-mono font-bold bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full border border-blue-200"
                  x-text="serialesEscaneados.length + ' de ' + productoSerialActual.cantidad + ' asignados'"></span>
        </div>

        <!-- Entrada con Pistola de Código de Barras -->
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Escanear Serial / IMEI:</label>
            <div class="flex gap-2">
                <input type="text" x-model="inputSerial" @keydown.enter.prevent="agregarSerialEscaneado()" placeholder="Escanee o tipee el número de serial..." class="flex-1 border rounded-xl p-2 text-xs font-mono">
                <button @click="agregarSerialEscaneado()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-xl text-xs font-semibold">
                    Asignar
                </button>
            </div>
        </div>

        <!-- Lista de Seriales Escaneados -->
        <div class="border rounded-xl p-3 bg-slate-50 max-h-48 overflow-y-auto space-y-1.5">
            <template x-for="(s, idx) in serialesEscaneados" :key="idx">
                <div class="flex justify-between items-center bg-white p-2 rounded-lg border text-xs font-mono">
                    <span class="font-bold text-slate-800" x-text="(idx + 1) + '. ' + s"></span>
                    <button @click="serialesEscaneados.splice(idx, 1)" class="text-red-500 hover:text-red-700 p-1">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </template>
            <div x-show="serialesEscaneados.length === 0" class="text-center py-6 text-slate-400 text-xs">
                Escanee los seriales requeridos para continuar.
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t">
            <button @click="cancelarAsignacionSeriales()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">Cancelar</button>
            <button @click="confirmarSerialesRenglon()" :disabled="serialesEscaneados.length !== productoSerialActual.cantidad" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded-xl text-xs font-bold">
                Confirmar Seriales
            </button>
        </div>
    </div>
</div>