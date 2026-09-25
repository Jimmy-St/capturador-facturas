@extends('layouts.app')

@section('title', isset($invoice) ? 'Factura #' . $invoice->folio . ' - ' . config('app.name') : 'Factura No Encontrada - ' . config('app.name'))

@section('content')
<!-- Viewer.js CSS CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css" />
<!-- Viewer.js JS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>
<!-- PDF.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    if (window.pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }
</script>

@if(isset($invoice))

<!-- CONTENEDOR RAÍZ CON TODAS LAS VARIABLES DE ALPINE -->
<div class="space-y-6" x-data="invoiceShow({
    invoiceId: {{ $invoice->id }},
    totalInvoice: {{ (float) $invoice->amount }},
    initialPaid: {{ (float) $invoice->totalPaid() }},
    paymentStatus: '{{ $invoice->payment_status }}',
    isReviewed: {{ $invoice->is_reviewed ? 'true' : 'false' }},
    originalImageUrl: '{{ $invoice->image_path ? asset('storage/' . $invoice->image_path) : '' }}',
    payments: {{ Js::from($invoice->payments->map(fn($p) => [
        'id' => $p->id,
        'amount' => (float) $p->amount,
        'formatted_amount' => number_format((float) $p->amount, 0, ',', '.'),
        'payment_method' => $p->payment_method,
        'payment_date' => $p->payment_date->format('d-m-Y'),
        'raw_date' => $p->payment_date->format('Y-m-d'),
        'reference_number' => $p->reference_number,
        'image_url' => asset('storage/' . $p->image_path),
        'notes' => $p->notes,
        'user_name' => optional($p->user)->username ?? optional($p->user)->name ?? 'Usuario',
        'created_at' => $p->created_at->format('d-m-Y H:i'),
    ])) }},
    routes: {
        review: '{{ route('invoices.review', $invoice->id) }}',
        storePayment: '{{ route('invoices.payments.store', $invoice->id) }}',
        deletePaymentBase: '{{ url('/invoices/' . $invoice->id . '/payments') }}',
        togglePaymentStatus: '{{ route('invoices.payments.status', $invoice->id) }}',
        csrf: '{{ csrf_token() }}'
    }
})">

    <!-- CAMPO HIDDEN DE TOKENS (Oculto al usuario) -->
    <input type="hidden" id="tokens_cost" value="{{ $invoice->tokens_cost }}">

    <!-- GRID PRINCIPAL DE 2 COLUMNAS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        
        <!-- Columna Izquierda: Datos Operativos Editables del Documento -->
        <div class="lg:col-span-2 space-y-5">
            
            <form action="{{ route('invoices.update', $invoice->id) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 sm:p-6 space-y-4">
                @csrf
                @method('PUT')

                <!-- Cabecera del Documento y Acción de Revisión -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 border-b border-gray-100 gap-3">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $invoice->document_type }}</span>
                        </div>
                        <h2 class="text-xl font-black text-gray-900 mt-0.5">
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

            <!-- Tarjeta para el JSON de Gemini / Raw -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="code" class="w-4 h-4 text-orange-500"></i>
                        <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider">Payload de Respuesta IA</h3>
                    </div>
                    <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md font-mono">JSON Raw</span>
                </div>
                <div class="bg-gray-900 rounded-lg p-3.5 overflow-x-auto text-[11px] font-mono text-emerald-400 max-h-56">
                    <pre><code>{{ $invoice->raw_response_json ? json_encode(json_decode($invoice->raw_response_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '// No hay datos registrados' }}</code></pre>
                </div>
            </div>

        </div>

        <!-- Columna Derecha: Imagen Original + Proceso Completo de Pago Unificado -->
        <div class="space-y-5">

            <!-- 1. Tarjeta: Factura Original (Captura Inicial con IA) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="file-check" class="w-4 h-4 text-orange-500"></i>
                        <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider">Documento Original</h3>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md uppercase
                        {{ $invoice->fidelity == 'alta' || $invoice->fidelity >= 90 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($invoice->fidelity == 'media' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                        Fiabilidad: {{ ucfirst($invoice->fidelity) }}
                    </span>
                </div>

                <!-- Contenedor miniatura con clic para abrir Viewer.js -->
                <div class="bg-gray-100 rounded-lg border border-gray-200 overflow-hidden flex flex-col items-center justify-center p-2 min-h-[180px] cursor-pointer group relative shadow-inner"
                     @click="openModal(originalImageUrl, 'Factura #{{ $invoice->folio }}')" title="Clic para ampliar documento original">
                    
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/15 transition-colors flex items-center justify-center z-10">
                        <span class="bg-white/95 text-gray-800 text-xs font-bold px-3 py-1.5 rounded-lg shadow-md opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-1.5">
                            <i data-lucide="zoom-in" class="w-4 h-4 text-orange-500"></i>
                            <span>Ver en grande</span>
                        </span>
                    </div>
                    
                    @if($invoice->image_path)
                        <img src="{{ asset('storage/' . $invoice->image_path) }}" alt="Factura #{{ $invoice->folio }}" class="max-h-[200px] w-full object-cover rounded-md shadow-sm">
                    @else
                        <div class="text-center p-6 space-y-2 text-gray-400 pointer-events-none">
                            <i data-lucide="image-off" class="w-8 h-8 mx-auto text-gray-300"></i>
                            <p class="text-xs">Sin imagen asociada</p>
                        </div>
                    @endif
                </div>

            </div>

            <!-- 2. RECUADRO ÚNICO UNIFICADO: ESTADO + REGISTRAR PAGO + HISTORIAL -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 space-y-5">
                
                <!-- SECCIÓN 1: ESTADO DE PAGO -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 text-sm">Estado de pago</h3>
                        <span class="text-xs font-black px-2.5 py-0.5 rounded-md uppercase tracking-wide"
                              :class="paymentStatus === 'pagado' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                              x-text="paymentStatus === 'pagado' ? 'PAGADO' : 'ADEUDADO'">
                        </span>
                    </div>

                    <!-- Métricas Financieras Sutiles -->
                    <div class="grid grid-cols-3 gap-2 py-2 text-center bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase">Total</span>
                            <span class="text-xs font-black text-gray-900" x-text="'$' + formatCLP(totalInvoice)"></span>
                        </div>
                        <div class="border-x border-gray-200">
                            <span class="block text-[10px] font-bold text-gray-400 uppercase">Abonado</span>
                            <span class="text-xs font-black text-emerald-600" x-text="'$' + formatCLP(totalPaid)"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase">Saldo</span>
                            <span class="text-xs font-black"
                                  :class="remainingAmount() <= 0.001 ? 'text-gray-400' : 'text-rose-600'"
                                  x-text="'$' + formatCLP(remainingAmount())"></span>
                        </div>
                    </div>

                    <!-- Botón de Conmutación de Estado -->
                    <button type="button"
                            @click="togglePaymentStatus()"
                            :disabled="isUpdatingStatus"
                            class="w-full py-2.5 px-4 rounded-lg font-bold text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer shadow-sm"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/20': paymentStatus === 'pagado',
                                'ring-2 ring-emerald-400 bg-emerald-600 hover:bg-emerald-700 text-white shadow-md animate-pulse': (paymentStatus === 'adeudado' && remainingAmount() <= 0.001),
                                'bg-slate-900 hover:bg-slate-800 text-white': (paymentStatus === 'adeudado' && remainingAmount() > 0.001)
                            }">
                        <!-- <i data-lucide="badge-dollar-sign" class="w-4 h-4"></i> -->
                        <span x-text="getStatusButtonText()"></span>
                    </button>

                    <!-- Barra fina de progreso con porcentaje lateral -->
                    <div class="flex items-center space-x-3 pt-1">
                        <div class="flex-1 bg-slate-100 h-1 rounded-full overflow-hidden border border-slate-200/50">
                            <div class="h-full transition-all duration-500"
                                 :class="percentPaid() >= 100 ? 'bg-emerald-500' : 'bg-orange-500'"
                                 :style="'width: ' + percentPaid() + '%'"></div>
                        </div>
                        <span class="text-[11px] font-bold text-gray-500 shrink-0 min-w-[36px] text-right" x-text="percentPaid() + '%'"></span>
                    </div>
                </div>

                <!-- SECCIÓN 2: REGISTRAR PAGO -->
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
                                <button type="button" 
                                        @click="newPayment.amount = remainingAmount()" 
                                        class="text-[11px] text-orange-600 hover:text-orange-700 font-bold underline cursor-pointer">
                                    Saldo sugerido
                                </button>
                            </div>
                            <input type="number" step="0.01" min="1" x-model="newPayment.amount" required
                                   placeholder="Ej. 50000"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-900 focus:bg-white focus:border-emerald-500 outline-none transition">
                        </div>

                        <!-- Fecha de Pago -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha del Pago</label>
                            <input type="date" x-model="newPayment.payment_date" required
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-semibold text-gray-800 focus:bg-white focus:border-emerald-500 outline-none transition">
                        </div>

                        <!-- Método de Pago y Referencia -->
                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Método</label>
                                <select x-model="newPayment.payment_method"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-2 text-xs font-semibold text-gray-800 focus:bg-white focus:border-emerald-500 outline-none transition">
                                    <option value="cheque">Cheque al Día</option>
                                    <option value="cheque_fecha">Cheque a Fecha</option>
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
                                    <p class="text-[10px] text-gray-400">Conversión automática de PDF a JPG</p>
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

                <!-- SECCIÓN 3: HISTORIAL DE PAGOS -->
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
                                        <span class="text-sm font-black text-gray-900" x-text="'$' + payment.formatted_amount"></span>
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
                                            <!-- <span class="font-semibold capitalize text-gray-500" x-text="payment.payment_method.replace('_', ' ')"></span> -->
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

            </div>

        </div>

    </div>

    <!-- Modal Alpine.js con Viewer.js Universal (Para Factura y Cheques) -->
    <div x-show="showModal" 
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/75 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeModal()" class="bg-white rounded-lg w-[85vw] h-[88vh] p-5 flex flex-col justify-between relative shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-sm flex items-center space-x-2">
                    <i data-lucide="scan-search" class="w-4 h-4 text-orange-500"></i>
                    <span x-text="modalTitle"></span>
                </h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-md cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Contenedor Viewer.js -->
            <div class="bg-gray-900 rounded-lg overflow-hidden flex-1 my-3 relative">
                <div id="image-viewer-container" class="w-full h-full">
                    <img id="image-viewer-target" :src="modalImageUrl" :alt="modalTitle" style="display:none;">
                </div>
            </div>

            <div class="flex justify-between items-center pt-2 text-xs text-gray-500">
                <span>Usa la rueda del ratón para hacer zoom y arrastra para mover la imagen.</span>
                <button @click="closeModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-1.5 px-4 rounded-lg transition-all cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</div>

@else
<!-- ESTADO: FACTURA NO ENCONTRADA -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-10 text-center max-w-xl mx-auto space-y-4 my-10">
    <div class="w-14 h-14 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto">
        <i data-lucide="file-search" class="w-7 h-7"></i>
    </div>
    <div class="space-y-1">
        <h3 class="text-base font-bold text-gray-800">Documento no encontrado</h3>
        <p class="text-xs text-gray-500">La factura que intentas buscar no existe o fue eliminada del sistema.</p>
    </div>
    <div class="pt-2">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white transition-all shadow-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Volver al Dashboard</span>
        </a>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    function invoiceShow(config) {
        return {
            invoiceId: config.invoiceId,
            totalInvoice: config.totalInvoice,
            totalPaid: config.initialPaid,
            paymentStatus: config.paymentStatus,
            isReviewed: config.isReviewed,
            originalImageUrl: config.originalImageUrl,
            payments: config.payments || [],
            routes: config.routes,

            // Estado de Formulario de Pago
            newPayment: {
                amount: Math.max(0, config.totalInvoice - config.initialPaid),
                payment_date: new Date().toISOString().split('T')[0],
                payment_method: 'cheque',
                reference_number: '',
                notes: ''
            },
            paymentFile: null,
            paymentPreview: null,
            fileName: '',
            isConvertingPdf: false,
            pdfProgress: '',
            isSubmittingPayment: false,
            paymentError: '',
            paymentSuccess: '',
            isUpdatingStatus: false,

            // Estado de Modal Viewer.js
            showModal: false,
            modalImageUrl: '',
            modalTitle: '',
            viewerInstance: null,

            init() {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            formatCLP(val) {
                const num = Math.round(Number(val) || 0);
                return num.toLocaleString('es-CL');
            },

            remainingAmount() {
                return Math.max(0, this.totalInvoice - this.totalPaid);
            },

            percentPaid() {
                if (!this.totalInvoice || this.totalInvoice <= 0) return 0;
                return Math.min(100, Math.round((this.totalPaid / this.totalInvoice) * 100));
            },

            getStatusButtonText() {
                if (this.paymentStatus === 'pagado') {
                    return 'CAMBIAR A ADEUDADO';
                }
                if (this.remainingAmount() <= 0.001) {
                    return '¿MARCAR COMO PAGADO?';
                }
                return '¿MARCAR COMO PAGADO?';
            },

            async markAsReviewed() {
                if (this.isReviewed) return;
                try {
                    const res = await fetch(this.routes.review, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': this.routes.csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.isReviewed = true;
                        this.$nextTick(() => lucide.createIcons());
                    }
                } catch (e) {
                    console.error('Error al marcar como revisada:', e);
                }
            },

            async togglePaymentStatus() {
                if (this.isUpdatingStatus) return;
                this.isUpdatingStatus = true;

                try {
                    const res = await fetch(this.routes.togglePaymentStatus, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': this.routes.csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.paymentStatus = data.payment_status;
                        this.$nextTick(() => lucide.createIcons());
                    }
                } catch (e) {
                    console.error('Error al actualizar estado:', e);
                } finally {
                    this.isUpdatingStatus = false;
                }
            },

            async onFileSelected(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.paymentError = '';
                this.fileName = file.name;

                // Detección de PDF para conversión en el navegador con PDF.js
                if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                    this.isConvertingPdf = true;
                    this.pdfProgress = 'Procesando PDF a imagen JPG...';

                    try {
                        const arrayBuffer = await file.arrayBuffer();
                        const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
                        const pdf = await loadingTask.promise;
                        const page = await pdf.getPage(1);

                        // Escala 2.0 para garantizar máxima legibilidad de números de cheque
                        const scale = 2.0;
                        const viewport = page.getViewport({ scale });

                        const canvas = document.createElement('canvas');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        const ctx = canvas.getContext('2d');

                        await page.render({ canvasContext: ctx, viewport }).promise;

                        canvas.toBlob((blob) => {
                            if (blob) {
                                this.paymentFile = new File([blob], file.name.replace(/\.pdf$/i, '.jpg'), { type: 'image/jpeg' });
                                this.paymentPreview = canvas.toDataURL('image/jpeg', 0.85);
                            } else {
                                this.paymentError = 'Error al convertir el PDF a formato de imagen.';
                            }
                            this.isConvertingPdf = false;
                            this.pdfProgress = '';
                            this.$nextTick(() => lucide.createIcons());
                        }, 'image/jpeg', 0.85);

                    } catch (err) {
                        console.error('Error procesando PDF:', err);
                        this.paymentError = 'No se pudo leer el documento PDF. Por favor selecciona una imagen JPG o PNG.';
                        this.isConvertingPdf = false;
                        this.paymentFile = null;
                        this.paymentPreview = null;
                    }

                } else {
                    // Imagen estándar (JPG, PNG, WebP)
                    this.paymentFile = file;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.paymentPreview = e.target.result;
                        this.$nextTick(() => lucide.createIcons());
                    };
                    reader.readAsDataURL(file);
                }
            },

            clearSelectedFile() {
                this.paymentFile = null;
                this.paymentPreview = null;
                this.fileName = '';
                const input = document.getElementById('payment-file-input');
                if (input) input.value = '';
                this.$nextTick(() => lucide.createIcons());
            },

            async submitPayment() {
                if (!this.paymentFile) {
                    this.paymentError = 'Debes adjuntar una imagen o PDF del comprobante o cheque.';
                    return;
                }
                if (this.newPayment.amount <= 0) {
                    this.paymentError = 'El monto debe ser superior a 0.';
                    return;
                }

                this.isSubmittingPayment = true;
                this.paymentError = '';
                this.paymentSuccess = '';

                const formData = new FormData();
                formData.append('amount', this.newPayment.amount);
                formData.append('payment_date', this.newPayment.payment_date);
                formData.append('payment_method', this.newPayment.payment_method);
                if (this.newPayment.reference_number) {
                    formData.append('reference_number', this.newPayment.reference_number);
                }
                if (this.newPayment.notes) {
                    formData.append('notes', this.newPayment.notes);
                }
                formData.append('image', this.paymentFile);

                try {
                    const res = await fetch(this.routes.storePayment, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.routes.csrf,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        this.paymentError = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Error al guardar el pago.');
                        return;
                    }

                    // Éxito: agregar pago a la lista reactiva y actualizar métricas
                    this.payments.push(data.payment);
                    this.totalPaid = data.total_paid;
                    this.paymentStatus = data.payment_status;

                    this.paymentSuccess = 'Pago registrado exitosamente.';
                    setTimeout(() => { this.paymentSuccess = ''; }, 4000);

                    // Resetear formulario
                    this.clearSelectedFile();
                    this.newPayment.amount = this.remainingAmount();
                    this.newPayment.reference_number = '';
                    this.newPayment.notes = '';

                    this.$nextTick(() => lucide.createIcons());

                } catch (e) {
                    console.error('Error al registrar pago:', e);
                    this.paymentError = 'Ocurrió un error inesperado al enviar el pago.';
                } finally {
                    this.isSubmittingPayment = false;
                }
            },

            async deletePayment(paymentId) {
                if (!confirm('¿Estás seguro de anular este pago? Se eliminará permanentemente el registro y el comprobante del servidor.')) {
                    return;
                }

                try {
                    const res = await fetch(`${this.routes.deletePaymentBase}/${paymentId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': this.routes.csrf,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();

                    if (data.success) {
                        this.payments = this.payments.filter(p => p.id !== paymentId);
                        this.totalPaid = data.total_paid;
                        this.paymentStatus = data.payment_status;
                        this.newPayment.amount = this.remainingAmount();
                        this.$nextTick(() => lucide.createIcons());
                    } else {
                        alert(data.message || 'No se pudo anular el pago.');
                    }
                } catch (e) {
                    console.error('Error al anular pago:', e);
                    alert('Error de conexión al intentar anular el pago.');
                }
            },

            openModal(imageUrl, title) {
                if (!imageUrl) return;
                this.modalImageUrl = imageUrl;
                this.modalTitle = title || 'Documento';
                this.showModal = true;

                this.$nextTick(() => {
                    setTimeout(() => {
                        const target = document.getElementById('image-viewer-target');
                        if (target) {
                            if (this.viewerInstance) {
                                this.viewerInstance.destroy();
                            }
                            this.viewerInstance = new Viewer(target, {
                                inline: true,
                                button: false,
                                toolbar: false,
                                navbar: false,
                                title: false,
                                tooltip: false,
                                movable: true,
                                zoomable: true,
                                scalable: false,
                                transition: false,
                                ready: function () {
                                    this.viewer.zoomTo(2.2);
                                }
                            });
                        }
                    }, 60);
                });
            },

            closeModal() {
                this.showModal = false;
                if (this.viewerInstance) {
                    this.viewerInstance.destroy();
                    this.viewerInstance = null;
                }
            }
        };
    }

    document.addEventListener("DOMContentLoaded", () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endpush

<style>
    .viewer-canvas, .viewer-move {
        cursor: move !important;
    }
    .viewer-canvas:active, .viewer-move:active {
        cursor: grabbing !important;
    }
</style>