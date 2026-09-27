<!-- ESTADO DE PAGO -->
<div class="space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="font-bold text-gray-900 text-sm">Estado de pago</h3>
        <span class="text-xs font-bold px-2.5 py-0.5 rounded-md uppercase tracking-wide"
              :class="paymentStatus === 'pagado' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
              x-text="paymentStatus === 'pagado' ? 'PAGADO' : 'ADEUDADO'">
        </span>
    </div>

    <!-- Métricas Financieras Sutiles -->
    <div class="grid grid-cols-3 gap-2 py-2 text-center bg-gray-50 rounded-lg border border-gray-100">
        <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase">Total</span>
            <span class="text-xs font-bold text-gray-900" x-text="'$' + formatCLP(totalInvoice)"></span>
        </div>
        <div class="border-x border-gray-200">
            <span class="block text-[10px] font-bold text-gray-400 uppercase">Abonado</span>
            <span class="text-xs font-bold text-emerald-600" x-text="'$' + formatCLP(totalPaid)"></span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase">Saldo</span>
            <span class="text-xs font-bold" :class="remainingAmount() <= 0.001 ? 'text-gray-400' : 'text-rose-600'" x-text="'$' + formatCLP(remainingAmount())"></span>
        </div>
    </div>

    <!-- Botón de Conmutación de Estado -->
    <button type="button" @click="togglePaymentStatus()" :disabled="isUpdatingStatus" class="w-full py-2.5 px-4 rounded-lg font-bold text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer shadow-sm"
        :class="{
            'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/20': paymentStatus === 'pagado',
            'ring-2 ring-emerald-400 bg-emerald-600 hover:bg-emerald-700 text-white shadow-md animate-pulse': (paymentStatus === 'adeudado' && remainingAmount() <= 0.001),
            'bg-slate-900 hover:bg-slate-800 text-white': (paymentStatus === 'adeudado' && remainingAmount() > 0.001)
        }">                        
        <span x-text="getStatusButtonText()"></span>
    </button>

    <!-- Barra de progreso -->
    <div class="flex items-center space-x-3 pt-1">
        <div class="flex-1 bg-slate-100 h-1 rounded-full overflow-hidden border border-slate-200/50">
            <div class="h-full transition-all duration-500" :class="percentPaid() >= 100 ? 'bg-emerald-500' : 'bg-orange-500'" :style="'width: ' + percentPaid() + '%'"></div>
        </div>
        <span class="text-[11px] font-bold text-gray-500 shrink-0 min-w-[36px] text-right" x-text="percentPaid() + '%'"></span>
    </div>
</div>
