@php
    $payload = !empty($invoice->raw_response_json) ? json_decode($invoice->raw_response_json, true) : null;
    $articulos = (is_array($payload) && !empty($payload['articulos']) && is_array($payload['articulos'])) 
        ? $payload['articulos'] 
        : [];
    $totalArticulos = count($articulos);
@endphp

<!-- Tarjeta de Ítems / Detalle del Documento -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 space-y-3">
    <!-- Cabecera -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i data-lucide="package" class="w-4 h-4 text-orange-500"></i>
            <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider">Ítems del Documento</h3>
        </div>
        <span class="text-[10px] {{ $totalArticulos > 0 ? 'bg-orange-50 text-orange-600 border border-orange-200' : 'bg-gray-100 text-gray-500' }} px-2 py-0.5 rounded-md font-semibold">
            {{ $totalArticulos }} {{ $totalArticulos === 1 ? 'ítem detectado' : 'ítems detectados' }}
        </span>
    </div>

    <!-- Contenedor con altura para ~6 filas y scroll vertical si sobrepasa -->
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="max-h-56 overflow-y-auto">
            @if($totalArticulos > 0)
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-gray-50 border-b border-gray-200 z-10">
                        <tr>
                            <th class="py-2 px-3 text-[11px] font-bold text-gray-600 uppercase tracking-wider w-36">
                                Código
                            </th>
                            <th class="py-2 px-3 text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                Detalle / Descripción
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @foreach($articulos as $item)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="py-2.5 px-3 font-mono font-semibold text-gray-700 whitespace-nowrap align-top">
                                    {{ !empty($item['codigo']) ? $item['codigo'] : '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-gray-800 font-medium align-top leading-relaxed">
                                    {{ !empty($item['descripcion']) ? $item['descripcion'] : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <!-- Estado vacío sin romper el diseño -->
                <div class="py-8 px-4 text-center space-y-1 text-gray-400">
                    <i data-lucide="inbox" class="w-6 h-6 mx-auto text-gray-300"></i>
                    <p class="text-xs font-semibold text-gray-500">No se registraron ítems en este documento</p>
                    <p class="text-[11px] text-gray-400">El documento no contiene líneas de detalle o no fueron reconocidas en el OCR.</p>
                </div>
            @endif
        </div>
    </div>
</div>
