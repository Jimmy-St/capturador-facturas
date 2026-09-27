<form action="{{ route('invoices.update', $invoice->id) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 sm:p-6 space-y-4">
    @csrf
    @method('PUT')

    <!-- Cabecera del Documento y Acción de Revisión -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 border-b border-gray-100 gap-3">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $invoice->document_type }}</span>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mt-0.5">
                #{{ $invoice->folio }}
            </h2>
        </div>

        <!-- Botón de Revisión de Factura -->
        <div>
            <button 
                type="button"
                @click="markAsReviewed()"
                :class="isReviewed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-default shadow-none' : 'bg-orange-500 hover:bg-orange-600 text-white shadow-sm shadow-orange-500/20 cursor-pointer'"
                class="py-2 px-3.5 rounded-lg font-bold text-xs flex items-center justify-center space-x-2 transition-all">
                
                <span x-show="!isReviewed" class="flex items-center space-x-1.5">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>MARCAR COMO REVISADA</span>
                </span>

                <span x-show="isReviewed" class="flex items-center space-x-1.5" style="display: none;">
                    <i data-lucide="check-check" class="w-4 h-4 text-emerald-600"></i>
                    <span>DOCUMENTO REVISADO</span>
                </span>
            </button>
        </div>
    </div>

    <!-- Grid de Campos Operativos Editables -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
        
        <!-- Folio del Documento -->
        <div class="space-y-1">
            <label class="block text-xs font-semibold text-gray-500">Folio del Documento</label>
            <input type="text" name="folio" value="{{ old('folio', $invoice->folio) }}" 
                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold text-gray-900 focus:bg-white focus:border-orange-500 outline-none transition">
        </div>

        <!-- Monto Total Facturado -->
        <div class="space-y-1">
            <label class="block text-xs font-semibold text-gray-500">Monto Total Facturado ($)</label>
            <input type="number" step="0.01" name="amount" value="{{ old('amount', $invoice->amount) }}" 
                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold text-orange-600 focus:bg-white focus:border-orange-500 outline-none transition">
        </div>

        <!-- Tipo de Documento -->
        <div class="space-y-1">
            <label class="block text-xs font-semibold text-gray-500">Tipo de Documento</label>
            <input type="text" name="document_type" value="{{ old('document_type', $invoice->document_type) }}" 
                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-800 focus:bg-white focus:border-orange-500 outline-none transition">
        </div>

        <!-- RUT Emisor -->
        <div class="space-y-1">
            <label class="block text-xs font-semibold text-gray-500">RUT Emisor</label>
            <input type="text" name="rut" value="{{ old('rut', $invoice->rut) }}" 
                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-800 focus:bg-white focus:border-orange-500 outline-none transition">
        </div>

        <!-- Razón Social / Supplier -->
        <div class="space-y-1 sm:col-span-2">
            <label class="block text-xs font-semibold text-gray-500">Razón Social / Proveedor</label>
            <input type="text" name="supplier" value="{{ old('supplier', $invoice->supplier) }}" 
                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-800 focus:bg-white focus:border-orange-500 outline-none transition">
        </div>

        <!-- Fecha de Emisión (Documento) - Editable -->
        <div class="space-y-1">
            <label class="block text-xs font-semibold text-gray-500">Fecha Emisión (Doc)</label>
            <input type="date" name="document_date" value="{{ old('document_date', optional($invoice->document_date)->format('Y-m-d')) }}" 
                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold text-gray-800 focus:bg-white focus:border-orange-500 outline-none transition">
        </div>

        <!-- Fecha de Recepción (Sistema) - NO EDITABLE -->
        <div class="space-y-1">
            <label class="block text-xs font-semibold text-gray-500">Fecha Recepción (Sistema)</label>
            <div class="w-full bg-gray-100/70 border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold text-gray-600">
                {{ optional($invoice->reception_date)->format('d-m-Y H:i:s') ?? 'N/A' }}
            </div>
        </div>
    </div>

    <!-- Botón Guardar Cambios -->
    <div class="flex justify-end pt-3 border-t border-gray-100">
        <button type="submit" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-lg text-xs font-bold bg-orange-500 hover:bg-orange-600 text-white transition-all shadow-sm cursor-pointer">
            <i data-lucide="save" class="w-4 h-4"></i>
            <span>GUARDAR DATOS FACTURA</span>
        </button>
    </div>

    <!-- Datos de Auditoría Interna -->
    <div class="pt-3 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center text-[11px] text-gray-400 gap-2">
        <div class="flex items-center space-x-1.5">
            <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i>
            <span>Capturado por: <strong class="text-gray-700">{{ optional($invoice->user)->username ?? optional($invoice->user)->name ?? 'Sistema' }}</strong></span>
        </div>
        <div class="flex items-center space-x-1.5">
            <i data-lucide="database" class="w-3.5 h-3.5 text-gray-400"></i>
            <span>Actualizado: {{ optional($invoice->updated_at)->format('d-m-Y H:i:s') }}</span>
        </div>
    </div>

</form>
