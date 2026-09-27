<!-- Tarjeta para el JSON de Gemini / Raw -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i data-lucide="code" class="w-4 h-4 text-orange-500"></i>
            <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider">Payload de Respuesta OCR</h3>
        </div>
        <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md font-mono">JSON Raw</span>
    </div>
    <div class="bg-zinc-800 border border-green-900 rounded-lg p-3.5 overflow-x-auto text-[11px] font-mono max-h-56 shadow-inner">
        <pre><code class="text-green-500">{{ $invoice->raw_response_json ? json_encode(json_decode($invoice->raw_response_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '// No hay datos registrados' }}</code></pre>
    </div>
</div>
