@extends('layouts.app')

@section('title', config('app.name') . ' - ' . config('app.tagline'))

@section('content')

        <!-- Invoices Table Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800 text-base">Listado de Facturas Recepcionadas</h2>
                <span class="text-xs bg-orange-50 text-orange-600 font-semibold px-2.5 py-1 rounded-full border border-orange-100">
                    {{ $invoices->total() ?? 0 }} Registros en total
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/75 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="py-3 px-4 font-bold">Folio</th>
                            <th class="py-3 px-4 font-bold">RUT Emisor / Receptor</th>
                            <th class="py-3 px-4 font-bold">Recepción</th>
                            <th class="py-3 px-4 font-bold">Monto</th>
                            <th class="py-3 px-4 font-bold">Estado</th>
                            <th class="py-3 px-4 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($invoices as $invoice)
                            <tr class="hover:bg-gray-200/50 transition-colors">
                                <td class="py-3 px-4 font-medium text-gray-900">#{{ $invoice->folio }}</td>
                                <td class="py-3 px-4 text-gray-600">
                                    <div class="text-xs font-bold text-gray-700">E: {{ $invoice->rut_emisor }}</div>
                                    <div class="text-xs text-gray-400">R: {{ $invoice->rut_receptor }}</div>
                                </td>
                                <td class="py-3 px-4 text-gray-600">
                                    <div class="text-xs font-bold text-gray-700">{{ \Carbon\Carbon::parse($invoice->reception_date)->format('d-m-Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($invoice->reception_date)->format('H:i:s') }}</div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-gray-800">
                                    ${{ number_format($invoice->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4">
                                    @if($invoice->status === 'revisada' || $invoice->status === 'fidelidad OK')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('invoices.show', $invoice->id) }}" class="inline-flex items-center justify-center text-gray-400 hover:text-orange-500 transition-colors p-1" title="Ver detalles">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-gray-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <i data-lucide="folder-open" class="w-10 h-10 text-gray-300"></i>
                                        <p class="text-sm font-medium text-gray-500">No hay facturas registradas o filtros coincidentes.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($invoices->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>

@endsection