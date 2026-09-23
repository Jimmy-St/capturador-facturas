@extends('layouts.app')

@section('title', config('app.name') . ' - ' . config('app.tagline'))

@section('content')

        <!-- Invoices Table Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            <!-- Cabecera con Filtros de Fidelidad -->
            <div class="p-5 border-b border-gray-100 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <h2 class="font-bold text-gray-800 text-base">Documentos Recepcionados</h2>
                
                <!-- Botones / Badges de Filtro por Fidelidad -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs text-gray-400 font-medium mr-1">Fidelidad Documento:</span>
                    
                    <a href="{{ route('dashboard') }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == '' ? 'bg-slate-900 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Todos
                    </a>
                    
                    <a href="{{ route('dashboard', ['fidelity' => 'baja']) }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'baja' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60' }}">
                        Baja
                    </a>

                    <a href="{{ route('dashboard', ['fidelity' => 'media']) }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'media' ? 'bg-orange-600 text-white shadow-sm' : 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200/60' }}">
                        Media
                    </a>

                    <a href="{{ route('dashboard', ['fidelity' => 'alta']) }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'alta' ? 'bg-sky-600 text-white shadow-sm' : 'bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200/60' }}">
                        Alta
                    </a>                        

                    <a href="{{ route('dashboard', ['fidelity' => 'vista']) }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'vista' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60' }}">
                        Vista
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/75 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="py-3 px-4 font-bold w-[15%]">Folio</th>
                            <th class="py-3 px-4 font-bold w-[40%]">RUT / Razón Social</th>
                            <th class="py-3 px-4 font-bold w-[20%]">Recepción</th>
                            <th class="py-3 px-4 font-bold w-[15%]">Monto</th>
                            <th class="py-3 px-4 font-bold w-[10%] text-right pr-6">Fidelidad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($invoices as $invoice)                            
                            <tr onclick="window.location='{{ route('invoices.show', $invoice->id) }}'" class="hover:bg-orange-50/40 transition-colors cursor-pointer group">
                                <td class="py-3.5 px-4 font-bold text-gray-900 group-hover:text-orange-600 transition-colors">
                                    #{{ $invoice->folio }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    <div class="text-xs font-bold text-gray-800">{{ $invoice->rut }}</div>                                    
                                    <div class="text-sm text-gray-400 font-medium truncate">{{ $invoice->supplier }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    <div class="text-xs font-bold text-gray-700">{{ \Carbon\Carbon::parse($invoice->reception_date)->format('d-m-Y') }}</div>
                                    <div class="text-[12px] text-gray-500">{{ \Carbon\Carbon::parse($invoice->reception_date)->format('H:i:s') }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-gray-800">
                                    ${{ number_format($invoice->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @php
                                        $status = strtolower($invoice->fidelity);
                                    @endphp

                                    @if($invoice->is_reviewed)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i data-lucide="circle-check" class="w-4 h-4"></i>
                                            <span>Vista</span>
                                        </span>
                                    @elseif($status === 'baja')
                                        <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                            <i data-lucide="gauge" class="w-4 h-4"></i>
                                            <span>Baja</span>
                                        </span>
                                    @elseif($status === 'media')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-200">
                                            <i data-lucide="gauge" class="w-4 h-4"></i>
                                            <span>Media</span>
                                        </span>
                                    @elseif($status === 'alta' || $status === 'fidelidad ok')
                                        <span class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200">
                                            <i data-lucide="gauge" class="w-4 h-4"></i>
                                            <span>Alta</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                            {{ ucfirst($invoice->fidelity) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-12 text-gray-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <i data-lucide="folder-open" class="w-10 h-10 text-gray-300"></i>
                                        <p class="text-sm font-medium text-gray-500">No hay documentos registrados o filtros coincidentes.</p>
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