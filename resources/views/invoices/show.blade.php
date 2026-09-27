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

<div class="space-y-2" x-data="invoiceShow({
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
            @include('components.invoice.form')
            @include('components.invoice.ocr-payload')
        </div>

        <!-- Columna Derecha: Imagen Original + Proceso Completo de Pago Unificado -->
        <div class="space-y-5">
            <!-- Tarjeta: Factura Original (Captura Inicial OCR) -->
            @include('components.invoice.image')
            
            <!-- RECUADRO UNIFICADO: ESTADO + REGISTRAR PAGO + HISTORIAL -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 space-y-5">
                @include('components.invoice.payment-status')
                @include('components.invoice.payment-form')
                @include('components.invoice.payment-history')
            </div>
        </div>
    </div>

    <!-- Modal Alpine.js con Viewer.js Universal -->
    @include('components.invoice.popup')
    
</div>

@else
<!-- ESTADO: FACTURA NO ENCONTRADA -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-10 text-center max-w-xl mx-auto space-y-4 my-10">
    <div class="w-20 h-20 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto">
        <i data-lucide="file-search" class="w-10 h-10"></i>
    </div>
    <div class="space-y-1">
        <h3 class="text-base font-bold text-gray-800">Documento no encontrado</h3>
        <p class="text-xs text-gray-500">La factura que intentas buscar no existe o fue eliminada del sistema.</p>
    </div>
</div>
@endif

@endsection

@push('scripts')
    @include('components.invoice.scripts')
@endpush