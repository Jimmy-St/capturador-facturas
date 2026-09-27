<!-- SECCIÓN: HISTORIAL DE PAGOS -->
<div class="pt-4 border-t border-gray-100 space-y-3">
    
    <div class="flex items-center justify-between">
        <h3 class="font-bold text-gray-900 text-sm">Historial de Pagos</h3>
        <span class="text-[11px] font-bold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md"
              x-text="payments.length + ' ' + (payments.length === 1 ? 'pago' : 'pagos')">
        </span>
    </div>

    <!-- Estado Vacío -->
    <div x-show="payments.length === 0" class="text-center py-6 space-y-1 text-gray-400">
        <i data-lucide="credit-card" class="w-8 h-8 mx-auto text-gray-300"></i>
        <p class="text-xs font-semibold">Sin pagos registrados aún</p>
    </div>

    <!-- Lista de Pagos Sin Recuadros Anidados -->
    <div class="space-y-4" x-show="payments.length > 0">
        <template x-for="(payment, index) in payments" :key="payment.id">
            
            <div class="border-b border-gray-100 pb-3 last:border-b-0 last:pb-0 space-y-2">
                
                <!-- Fila Principal del Pago -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-[11px] font-bold text-emerald-700" x-text="'Pago #' + (index + 1)"></span>
                        <span class="text-sm font-bold text-gray-900" x-text="'$' + payment.formatted_amount"></span>
                    </div>
                    
                    <button type="button" 
                            @click="deletePayment(payment.id)"
                            title="Anular pago y eliminar comprobante"
                            class="text-gray-400 hover:text-rose-600 p-1 rounded-md transition-colors cursor-pointer">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Miniatura Comprobante + Detalles -->
                <div class="flex space-x-3 items-start">
                    <div class="w-20 h-16 bg-gray-100 rounded-lg overflow-hidden shrink-0 relative cursor-pointer group/img border border-gray-200"
                         @click="openModal(payment.image_url, 'Comprobante Pago #' + (index + 1) + ' - $' + payment.formatted_amount)">
                        <img :src="payment.image_url" alt="Comprobante" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/0 group-hover/img:bg-black/20 transition-colors flex items-center justify-center">
                            <i data-lucide="zoom-in" class="w-4 h-4 text-white opacity-0 group-hover/img:opacity-100 transition-opacity"></i>
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-600 space-y-0.5 flex-1 min-w-0">
                        <div class="flex justify-between text-gray-700">
                            <span class="font-bold" x-text="payment.payment_date"></span>
                        </div>
                        <div x-show="payment.reference_number" class="text-gray-800 font-mono truncate">
                            <span class="font-bold" x-text="payment.payment_method.replace('_', ' ')"></span> - <span class="font-bold" x-text="payment.reference_number"></span>
                        </div>
                        <div x-show="payment.notes" class="text-gray-500 italic truncate" x-text="payment.notes"></div>
                        <div class="text-[10px] text-gray-400 pt-0.5">
                            Por <span x-text="payment.user_name"></span> &bull; <span x-text="payment.created_at"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
