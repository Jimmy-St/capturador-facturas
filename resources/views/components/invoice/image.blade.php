<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">      
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i data-lucide="scan-search" class="w-4 h-4 text-orange-500"></i>
            <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider">Captura</h3>
        </div>                    
        <span class="text-[10px]">
            Fiabilidad OCR: <x-fidelity-badge :status="$invoice->fidelity" />
        </span>
    </div>
    <!-- Contenedor miniatura con clic para abrir Viewer.js -->
    <div class="bg-gray-100 rounded-lg border border-gray-200 overflow-hidden flex flex-col items-center justify-center p-2 min-h-[180px] cursor-pointer group relative shadow-inner" @click="openModal(originalImageUrl, 'Factura #{{ $invoice->folio }}')" title="Clic para ampliar documento original">
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/15 transition-colors flex items-center justify-center z-10">
            <span class="bg-white/95 text-gray-800 text-xs font-bold px-3 py-1.5 rounded-lg shadow-md opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-1.5">
                <i data-lucide="zoom-in" class="w-4 h-4 text-orange-500"></i>
                <span>Ver en grande</span>
            </span>
        </div>
        @if($invoice->image_path)
            <img src="{{ asset('storage/' . $invoice->image_path) }}" alt="Factura #{{ $invoice->folio }}" class="max-h-[200px] w-full object-cover rounded-md shadow-sm">
        @else
            <div class="max-h-[200px] text-center p-6 space-y-2 text-gray-400 pointer-events-none">
                <i data-lucide="image-off" class="w-10 h-10 mx-auto text-gray-300"></i>
                <p class="text-xs">Sin imagen asociada</p>
            </div>
        @endif
    </div>
</div>