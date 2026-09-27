<!-- REGISTRAR PAGO -->
<div class="pt-4 border-t border-gray-100 space-y-3">                    
    <h3 class="font-bold text-gray-900 text-sm">Registrar Pago</h3>

    <!-- Mensajes de feedback -->
    <div x-show="paymentError" x-text="paymentError" class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-xs font-semibold text-rose-700" style="display: none;"></div>
    <div x-show="paymentSuccess" x-text="paymentSuccess" class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-semibold text-emerald-700" style="display: none;"></div>

    <form @submit.prevent="submitPayment()" class="space-y-3">                        
        <!-- Monto y Sugerencia de Saldo -->
        <div>
            <div class="flex justify-between items-center mb-1">
                <label class="text-xs font-semibold text-gray-600">Monto del Pago ($)</label>
                <button type="button" @click="newPayment.amount = remainingAmount()" class="text-[11px] text-orange-600 hover:text-orange-700 font-bold underline cursor-pointer">
                    Saldo sugerido
                </button>
            </div>
            <input type="number" step="0.01" min="1" x-model="newPayment.amount" required placeholder="Ej. 50000" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-900 focus:bg-white focus:border-emerald-500 outline-none transition">
        </div>

        <!-- Fecha de Pago -->
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha del Pago</label>
            <input type="date" x-model="newPayment.payment_date" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold text-gray-800 focus:bg-white focus:border-emerald-500 outline-none transition">
        </div>

        <!-- Método de Pago y Referencia -->
        <div class="grid grid-cols-2 gap-2.5">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Método</label>
                <select x-model="newPayment.payment_method" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-2 text-xs font-semibold text-gray-800 focus:bg-white focus:border-emerald-500 outline-none transition">
                    <option value="cheque">Cheque</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">N° Cheque / Ref</label>
                <input type="text" x-model="newPayment.reference_number" placeholder="Ej. CHQ-9912"
                       class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-2 text-xs font-semibold text-gray-800 focus:bg-white focus:border-emerald-500 outline-none transition">
            </div>
        </div>

        <!-- Observaciones -->
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Observaciones (Opcional)</label>
            <input type="text" x-model="newPayment.notes" placeholder="Ej. Banco Estado / Tesorería"
                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:border-emerald-500 outline-none transition">
        </div>

        <!-- Selector de Comprobante -->
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Comprobante (Imagen o PDF)</label>
            
            <div class="border border-dashed border-gray-300 rounded-lg p-2.5 text-center hover:border-emerald-500 transition-colors bg-gray-50/50">
                
                <input type="file" id="payment-file-input" @change="onFileSelected($event)" accept="image/*,.pdf,application/pdf" class="hidden">
                
                <!-- Estado: Sin archivo -->
                <div x-show="!paymentPreview && !isConvertingPdf" class="space-y-1 py-1.5 cursor-pointer" @click="document.getElementById('payment-file-input').click()">
                    <i data-lucide="upload-cloud" class="w-6 h-6 mx-auto text-gray-400"></i>
                    <p class="text-xs font-bold text-gray-700">Subir foto o PDF</p>
                    <p class="text-[10px] text-gray-400">Conversión automática a JPG</p>
                </div>

                <!-- Estado: Convirtiendo -->
                <div x-show="isConvertingPdf" class="py-3 space-y-1.5 text-center" style="display: none;">
                    <div class="inline-block animate-spin rounded-full h-5 w-5 border-2 border-emerald-500 border-t-transparent"></div>
                    <p class="text-xs font-bold text-emerald-700" x-text="pdfProgress"></p>
                </div>

                <!-- Estado: Listo -->
                <div x-show="paymentPreview && !isConvertingPdf" class="relative group" style="display: none;">
                    <img :src="paymentPreview" alt="Previsualización" class="max-h-32 mx-auto rounded-md shadow-sm border border-gray-200 object-cover">
                    <div class="flex items-center justify-between mt-1.5 px-1">
                        <span class="text-[10px] font-semibold text-gray-600 truncate max-w-[180px]" x-text="fileName"></span>
                        <button type="button" @click="clearSelectedFile()" class="text-rose-600 hover:text-rose-800 text-[11px] font-bold">
                            Cambiar
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Botón Guardar Pago -->
        <button type="submit" 
                :disabled="isSubmittingPayment || isConvertingPdf || !paymentFile || newPayment.amount <= 0"
                class="w-full py-2.5 px-4 rounded-lg font-bold text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer"
                :class="(isSubmittingPayment || isConvertingPdf || !paymentFile || newPayment.amount <= 0) ? 'bg-gray-200 text-gray-400 cursor-not-allowed shadow-none' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm shadow-emerald-500/20'">
            <template x-if="isSubmittingPayment">
                <span class="flex items-center space-x-2">
                    <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>REGISTRANDO PAGO...</span>
                </span>
            </template>
            <template x-if="!isSubmittingPayment">
                <span class="flex items-center space-x-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>GUARDAR INSTANCIA DE PAGO</span>
                </span>
            </template>
        </button>

    </form>
</div>
