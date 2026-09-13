@extends('layouts.app')

@section('title', 'Factura #' . $invoice->folio . ' - Talos')

@section('content')
<div x-data="{ showModal: false }" class="space-y-6">

    <!-- Top Header bar limpio -->
    <div class="flex items-center justify-between">
        <h2 class="font-bold text-2xl text-gray-900">Factura #{{ $invoice->folio }}</h2>

        <div>
            @if($invoice->status === 'revisada' || $invoice->status === 'fidelidad OK' || $invoice->status === 'visado')
                <span class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    <span>REVISADA</span>
                </span>
            @else
                <form action="{{ route('invoices.update-status', $invoice->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-xs font-bold bg-orange-500 hover:bg-orange-600 text-white transition-all shadow-sm shadow-orange-500/20 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>MARCAR COMO REVISADA</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Columna Principal: Datos de la Factura -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Tarjeta Principal de Información -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8 space-y-6">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b border-gray-100 gap-4">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Folio del Documento</span>
                        <h3 class="text-2xl font-bold text-gray-900">#{{ $invoice->folio }}</h3>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Monto Total</span>
                        <div class="text-2xl font-bold text-orange-600">${{ number_format($invoice->amount, 0, ',', '.') }}</div>
                    </div>
                </div>

                <!-- Grid de Campos Operativos -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                    <!-- Emisor -->
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                        <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                            <i data-lucide="building-2" class="w-4 h-4 text-orange-500"></i>
                            <span>RUT Emisor</span>
                        </div>
                        <div class="text-base font-bold text-gray-800">{{ $invoice->rut_emisor }}</div>
                    </div>

                    <!-- Receptor -->
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                        <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                            <i data-lucide="user-check" class="w-4 h-4 text-orange-500"></i>
                            <span>RUT Receptor</span>
                        </div>
                        <div class="text-base font-bold text-gray-800">{{ $invoice->rut_receptor }}</div>
                    </div>

                    <!-- Fecha de Emisión (Documento) -->
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                        <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                            <i data-lucide="calendar" class="w-4 h-4 text-orange-500"></i>
                            <span>Fecha Emisión (Doc)</span>
                        </div>
                        <div class="text-base font-semibold text-gray-800">
                            {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}
                        </div>
                    </div>

                    <!-- Fecha de Recepción (Sistema) -->
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                        <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                            <i data-lucide="clock" class="w-4 h-4 text-orange-500"></i>
                            <span>Fecha Recepción (Sistema)</span>
                        </div>
                        <div class="text-base font-semibold text-gray-800">
                            {{ $invoice->reception_date ? \Carbon\Carbon::parse($invoice->reception_date)->format('d-m-Y H:i:s') : 'N/A' }}
                        </div>
                    </div>
                </div>

                <!-- Datos de Auditoría Interna -->
                <div class="pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-gray-500">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                        <span>Registrado por: <strong class="text-gray-700">{{ $invoice->user->username ?? 'Sistema' }}</strong></span>
                    </div>
                    <div class="flex items-center space-x-2 sm:justify-end">
                        <i data-lucide="database" class="w-4 h-4 text-gray-400"></i>
                        <span>Actualizado: {{ $invoice->updated_at ? $invoice->updated_at->format('d-m-Y H:i:s') : ($invoice->reception_date ? \Carbon\Carbon::parse($invoice->reception_date)->format('d-m-Y H:i:s') : 'N/A') }}</span>
                    </div>
                </div>

            </div>

            <!-- Tarjeta para el JSON de AWS -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="code" class="w-5 h-5 text-orange-500"></i>
                        <h3 class="font-bold text-gray-800 text-sm">Payload de Respuesta</h3>
                    </div>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-md font-mono">JSON Raw</span>
                </div>
                <div class="bg-gray-900 rounded-xl p-4 overflow-x-auto text-xs font-mono text-emerald-400 max-h-60">
                    <pre>{{ $invoice->aws_response_json ? json_encode(json_decode($invoice->aws_response_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '// No hay datos de AWS registrados' }}</pre>
                </div>
            </div>

        </div>

        <!-- Columna Lateral: Miniatura Interactiva -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4 sticky top-36">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="image-down" class="w-5 h-5 text-orange-500"></i>
                        <h3 class="font-bold text-gray-800 text-sm">Imagen del Documento</h3>
                    </div>
                    <span class="text-xs text-gray-400 font-medium">Captura</span>
                </div>

                <!-- Contenedor miniatura con evento Alpine para abrir modal -->
                <div class="bg-gray-100 rounded-xl border border-gray-200 overflow-hidden flex flex-col items-center justify-center p-3 min-h-[260px] cursor-pointer group relative shadow-inner"
                     @click="showModal = true" title="Hacer clic para ampliar imagen">
                    
                    @if($invoice->image_path)
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center z-10">
                            <span class="bg-white/95 text-gray-800 text-xs font-bold px-3 py-2 rounded-xl shadow-md opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-1.5">
                                <i data-lucide="zoom-in" class="w-4 h-4 text-orange-500"></i>
                                <span>Ver en grande</span>
                            </span>
                        </div>
                        <!-- Vista previa miniatura real de la imagen -->
                        <img src="{{ asset('storage/' . $invoice->image_path) }}" alt="Factura #{{ $invoice->folio }}" class="max-h-[220px] w-full object-cover rounded-lg shadow-sm">
                    @else
                        <div class="text-center p-6 space-y-2 text-gray-400 pointer-events-none">
                            <i data-lucide="image-off" class="w-10 h-10 mx-auto text-gray-300"></i>
                            <p class="text-xs">Sin imagen asociada</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Alpine.js para vista ampliada -->
    <div x-show="showModal" 
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="showModal = false" class="bg-white rounded-2xl max-w-5xl w-full p-6 space-y-4 relative shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-base flex items-center space-x-2">
                    <i data-lucide="image" class="w-5 h-5 text-orange-500"></i>
                    <span>Vista Detallada - Factura #{{ $invoice->folio }}</span>
                </h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 overflow-auto max-h-[75vh] border border-gray-100">
                @if($invoice->image_path)
                    <img src="{{ asset('storage/' . $invoice->image_path) }}" alt="Factura #{{ $invoice->folio }}" class="rounded-lg shadow-md block">
                @else
                    <div class="text-center space-y-3 py-20">
                        <i data-lucide="image-off" class="w-16 h-16 text-gray-300 mx-auto"></i>
                        <p class="text-xs text-gray-400">Sin imagen disponible</p>
                    </div>
                @endif
            </div>

            <div class="flex justify-end pt-2">
                <button @click="showModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-xl text-xs transition-all cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Asegurar Alpine.js cargado si no estuviera en el layout -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection