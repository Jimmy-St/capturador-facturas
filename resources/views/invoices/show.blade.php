@extends('layouts.app')

@section('title', isset($invoice) ? 'Factura #' . $invoice->folio . ' - ' . config('app.name') : 'Factura No Encontrada - ' . config('app.name'))

@section('content')
<!-- Viewer.js CSS CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css" />

<!-- Viewer.js JS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>

<!-- CONTENEDOR RAÍZ CON TODAS LAS VARIABLES DE ALPINE -->
<div class="space-y-1" x-data="{ isReviewed: {{ $invoice && $invoice->is_reviewed ? 'true' : 'false' }}, showModal: false }">

    @if(isset($invoice))
        
        <!-- CAMPO HIDDEN DE TOKENS (Oculto al usuario) -->
        <input type="hidden" id="tokens_cost" value="{{ $invoice->tokens_cost }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Columna Principal: Datos Editables de la Factura -->
            <div class="lg:col-span-2 space-y-6">
                
                <form action="{{ route('invoices.update', $invoice->id) }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b border-gray-100 gap-4">
                        <div>
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Folio del Documento</span>
                            <input type="text" name="folio" value="{{ old('folio', $invoice->folio) }}" 
                                class="text-2xl font-bold text-gray-900 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1 w-full sm:w-48 focus:bg-white focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none transition mt-1">
                        </div>
                        <div class="text-left sm:text-right w-full sm:w-auto">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Monto Total</span>
                            <input type="number" step="0.01" name="amount" value="{{ old('amount', $invoice->amount) }}" 
                                class="text-2xl font-bold text-orange-600 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1 w-full sm:w-48 text-left sm:text-right focus:bg-white focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none transition mt-1">
                        </div>
                    </div>

                    <!-- Grid de Campos Operativos Editables -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                        
                        <!-- Tipo de Documento -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                            <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                                <i data-lucide="file-text" class="w-4 h-4 text-orange-500"></i>
                                <span>Tipo de Documento</span>
                            </div>
                            <input type="text" name="document_type" value="{{ old('document_type', $invoice->document_type) }}" 
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold text-gray-800 focus:border-orange-500 outline-none transition">
                        </div>

                        <!-- RUT Emisor -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                            <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                                <i data-lucide="building-2" class="w-4 h-4 text-orange-500"></i>
                                <span>RUT Emisor</span>
                            </div>
                            <input type="text" name="rut" value="{{ old('rut', $invoice->rut) }}" 
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold text-gray-800 focus:border-orange-500 outline-none transition">
                        </div>

                        <!-- Razón Social / Supplier -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1 sm:col-span-2">
                            <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                                <i data-lucide="store" class="w-4 h-4 text-orange-500"></i>
                                <span>Razón Social / Proveedor</span>
                            </div>
                            <input type="text" name="supplier" value="{{ old('supplier', $invoice->supplier) }}" 
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold text-gray-800 focus:border-orange-500 outline-none transition">
                        </div>

                        <!-- Fecha de Emisión (Documento) - Editable -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                            <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                                <i data-lucide="calendar" class="w-4 h-4 text-orange-500"></i>
                                <span>Fecha Emisión (Doc)</span>
                            </div>
                            <input type="date" name="document_date" value="{{ old('document_date', optional($invoice->document_date)->format('Y-m-d')) }}" 
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm font-semibold text-gray-800 focus:border-orange-500 outline-none transition">
                        </div>

                        <!-- Fecha de Recepción (Sistema) - NO EDITABLE -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                            <div class="flex items-center space-x-2 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                                <i data-lucide="clock" class="w-4 h-4 text-orange-500"></i>
                                <span>Fecha Recepción (Sistema)</span>
                            </div>
                            <div class="text-sm font-semibold text-gray-600 py-2">
                                {{ optional($invoice->reception_date)->format('d-m-Y H:i:s') ?? 'N/A' }}
                            </div>
                        </div>
                    </div>

                    <!-- Botón Guardar Cambios (Naranja Institucional) -->
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-xl text-xs font-bold bg-orange-500 hover:bg-orange-600 text-white transition-all shadow-sm shadow-orange-500/20 cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>GUARDAR CAMBIOS</span>
                        </button>
                    </div>

                    <!-- Datos de Auditoría Interna -->
                    <div class="pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-gray-500">
                        <div class="flex items-center space-x-2">
                            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                            <span>Registrado por: <strong class="text-gray-700">{{ optional($invoice->user)->name ?? 'Sistema' }}</strong></span>
                        </div>
                        <div class="flex items-center space-x-2 sm:justify-end">
                            <i data-lucide="database" class="w-4 h-4 text-gray-400"></i>
                            <span>Actualizado: {{ optional($invoice->updated_at)->format('d-m-Y H:i:s') }}</span>
                        </div>
                    </div>

                </form>

                <!-- Tarjeta para el JSON de Gemini / Raw -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i data-lucide="code" class="w-5 h-5 text-orange-500"></i>
                            <h3 class="font-bold text-gray-800 text-sm">Payload de Respuesta</h3>
                        </div>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-md font-mono">JSON Raw</span>
                    </div>
                    <div class="bg-gray-900 rounded-xl p-4 overflow-x-auto text-xs font-mono text-emerald-400 max-h-60">
                        <pre><code>{{ $invoice->raw_response_json ? json_encode(json_decode($invoice->raw_response_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '// No hay datos registrados' }}</code></pre>
                    </div>
                </div>

            </div>

            <!-- Columna Lateral: Imagen, Fidelidad y Botón de Revisión -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6 sticky top-36">
                    
                    <!-- Header Tarjeta Lateral con Estado de Fidelidad -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i data-lucide="image-down" class="w-5 h-5 text-orange-500"></i>
                            <h3 class="font-bold text-gray-800 text-sm">Documento</h3>
                        </div>
                        <!-- Badge de Fidelidad Dinámico -->
                        <span class="text-xs font-bold px-2.5 py-1 rounded-lg uppercase
                            {{ $invoice->fidelity == 'alta' || $invoice->fidelity >= 90 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($invoice->fidelity == 'media' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                            Fiabilidad: {{ ucfirst($invoice->fidelity) }}
                        </span>
                    </div>

                    <!-- Contenedor miniatura con evento Alpine para abrir modal -->
                    <div class="bg-gray-100 rounded-xl border border-gray-200 overflow-hidden flex flex-col items-center justify-center p-3 min-h-[220px] cursor-pointer group relative shadow-inner"
                         @click="showModal = true" title="Hacer clic para ampliar imagen">
                        
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center z-10">
                            <span class="bg-white/95 text-gray-800 text-xs font-bold px-3 py-2 rounded-xl shadow-md opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-1.5">
                                <i data-lucide="zoom-in" class="w-4 h-4 text-orange-500"></i>
                                <span>Ver en grande</span>
                            </span>
                        </div>
                        
                        @if($invoice->image_path)
                            <img src="{{ asset('storage/' . $invoice->image_path) }}" alt="Factura #{{ $invoice->folio }}" class="max-h-[220px] w-full object-cover rounded-lg shadow-sm">
                        @else
                            <div class="text-center p-6 space-y-2 text-gray-400 pointer-events-none">
                                <i data-lucide="image-off" class="w-10 h-10 mx-auto text-gray-300"></i>
                                <p class="text-xs">Sin imagen asociada</p>
                            </div>
                        @endif
                    </div>

                    <!-- Botón de Revisión -->
                    <div>
                        <button 
                            @click="
                                if (!isReviewed) {
                                    fetch('{{ route('invoices.review', $invoice->id) }}', {
                                        method: 'PATCH',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json'
                                        }
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if(data.success) {
                                            isReviewed = true;
                                        }
                                    });
                                }
                            "
                            :class="isReviewed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-default shadow-none' : 'bg-orange-500 hover:bg-orange-600 text-white shadow-md shadow-orange-500/20 cursor-pointer'"
                            class="w-full py-3 px-4 rounded-xl font-bold text-xs flex items-center justify-center space-x-2 transition-all">
                            
                            <span x-show="!isReviewed" class="flex items-center space-x-2">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                <span>MARCAR COMO REVISADA</span>
                            </span>

                            <span x-show="isReviewed" class="flex items-center space-x-2" style="display: none;">
                                <i data-lucide="check-check" class="w-4 h-4 text-emerald-600"></i>
                                <span>DOCUMENTO REVISADO</span>
                            </span>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Modal Alpine.js con Viewer.js (Ancho 80vw y Alto 85vh) -->
            <div x-show="showModal" 
                 x-transition.opacity
                 x-init="$watch('showModal', value => {
                    if (value) {
                        setTimeout(() => {
                            const image = document.getElementById('image-viewer-target');
                            if (image && !image.viewer) {
                                const viewer = new Viewer(image, {
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
                                        // Forzamos un zoom inicial al 100% o 1.5 apenas esté lista
                                        this.viewer.zoomTo(2.2); 
                                    }
                                });
                            }
                        }, 50);
                    }
                 })"
                 class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4"
                 style="display: none;">
                
                <div @click.away="showModal = false" class="bg-white rounded-2xl w-[80vw] h-[85vh] p-6 flex flex-col justify-between relative shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-900 text-base flex items-center space-x-2">
                            <i data-lucide="scan-search" class="w-5 h-5 text-orange-500"></i>
                            <span>{{ $invoice->document_type }} #{{ $invoice->folio }}</span>
                        </h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg cursor-pointer">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Contenedor Viewer.js (Ocupa el espacio disponible) -->
                    <div class="bg-gray-900 rounded-xl overflow-hidden flex-1 my-4 relative">
                        @if($invoice->image_path)
                            <div id="image-viewer-target" class="w-full h-full">
                                <img src="{{ asset('storage/' . $invoice->image_path) }}" alt="Factura #{{ $invoice->folio }}" style="display:none;">
                            </div>
                        @else
                            <div class="text-center space-y-3 py-20 text-gray-400 h-full flex flex-col items-center justify-center">
                                <i data-lucide="image-off" class="w-16 h-16 mx-auto"></i>
                                <p class="text-xs">Sin imagen disponible</p>
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

    @else
        <!-- ESTADO: FACTURA NO ENCONTRADA -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center max-w-xl mx-auto space-y-4 my-12">
            <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto">
                <i data-lucide="file-search" class="w-8 h-8"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-lg font-bold text-gray-800">Documento no encontrado</h3>
                <p class="text-sm text-gray-500">La factura que intentas buscar no existe o fue eliminada del sistema.</p>
            </div>
            <div class="pt-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white transition-all shadow-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Volver al Dashboard</span>
                </a>
            </div>
        </div>
    @endif

</div>
@endsection

<!-- Script para inicializar Lucide Icons -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
<style>
    /* Cambiar la manito pixelada por un cursor de desplazamiento profesional */
    .viewer-canvas, .viewer-move {
        cursor: move !important; /* O puedes usar grab / crosshair */
    }
    .viewer-canvas:active, .viewer-move:active {
        cursor: grabbing !important;
    }
</style>