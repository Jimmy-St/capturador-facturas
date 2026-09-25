@extends('layouts.app')

@section('title', config('app.name') . ' - ' . config('app.tagline'))

@section('content')

<div class="space-y-6">

    <!-- KPI STATS SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        
        <!-- Total Documentos -->
        <a href="{{ route('dashboard') }}" 
           class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-all group flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Total Recepcionados</span>
                <span class="text-2xl font-black text-gray-900 mt-1 block">{{ number_format($stats['total_count'] ?? 0, 0, ',', '.') }}</span>
                <span class="text-xs text-gray-500 font-medium">Documentos en sistema</span>
            </div>
            <div class="p-3 bg-gray-100 group-hover:bg-orange-50 text-gray-600 group-hover:text-orange-600 rounded-xl transition-colors">
                <i data-lucide="files" class="w-6 h-6"></i>
            </div>
        </a>

        <!-- Total Adeudados -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['payment_status' => 'adeudado'])) }}" 
           class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-all group flex items-center justify-between {{ request('payment_status') === 'adeudado' ? 'ring-2 ring-amber-400 bg-amber-50/20' : '' }}">
            <div>
                <span class="text-xs font-bold text-amber-700 uppercase tracking-wider block flex items-center space-x-1">
                    <i data-lucide="clock-alert" class="w-3.5 h-3.5 text-amber-600"></i>
                    <span>Documentos Adeudados</span>
                </span>
                <span class="text-2xl font-black text-amber-700 mt-1 block">{{ number_format($stats['adeudado_count'] ?? 0, 0, ',', '.') }}</span>
                <span class="text-xs font-semibold text-rose-600">$ {{ number_format($stats['total_amount_adeudado'] ?? 0, 0, ',', '.') }} CLP adeudado</span>
            </div>
            <div class="p-3 bg-amber-100 text-amber-700 rounded-xl">
                <i data-lucide="receipt text-amber-600" class="w-6 h-6"></i>
            </div>
        </a>

        <!-- Total Pagados -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['payment_status' => 'pagado'])) }}" 
           class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-all group flex items-center justify-between {{ request('payment_status') === 'pagado' ? 'ring-2 ring-emerald-400 bg-emerald-50/20' : '' }}">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider block flex items-center space-x-1">
                    <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Documentos Pagados</span>
                </span>
                <span class="text-2xl font-black text-emerald-700 mt-1 block">{{ number_format($stats['pagado_count'] ?? 0, 0, ',', '.') }}</span>
                <span class="text-xs font-semibold text-emerald-600">$ {{ number_format($stats['total_amount_pagado'] ?? 0, 0, ',', '.') }} CLP pagado</span>
            </div>
            <div class="p-3 bg-emerald-100 text-emerald-700 rounded-xl">
                <i data-lucide="badge-dollar-sign" class="w-6 h-6"></i>
            </div>
        </a>

    </div>

    <!-- Invoices Table Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Cabecera con Filtros de Estado y Fidelidad -->
        <div class="p-5 border-b border-gray-100 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-gray-900 text-base">Documentos Recepcionados</h2>
                <p class="text-xs text-gray-500 font-medium">Auditoría, revisión e historial de pagos por documento</p>
            </div>
            
            <!-- Botones / Badges de Filtro por Fidelidad -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs text-gray-400 font-medium mr-1">Fidelidad IA:</span>
                
                <a href="{{ route('dashboard', request()->except('fidelity')) }}" 
                   class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == '' ? 'bg-slate-900 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Todas
                </a>
                
                <a href="{{ route('dashboard', array_merge(request()->query(), ['fidelity' => 'baja'])) }}" 
                   class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'baja' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60' }}">
                    Baja
                </a>

                <a href="{{ route('dashboard', array_merge(request()->query(), ['fidelity' => 'media'])) }}" 
                   class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'media' ? 'bg-orange-600 text-white shadow-sm' : 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200/60' }}">
                    Media
                </a>

                <a href="{{ route('dashboard', array_merge(request()->query(), ['fidelity' => 'alta'])) }}" 
                   class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'alta' ? 'bg-sky-600 text-white shadow-sm' : 'bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200/60' }}">
                    Alta
                </a>                        

                <a href="{{ route('dashboard', array_merge(request()->query(), ['fidelity' => 'vista'])) }}" 
                   class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'vista' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60' }}">
                    Vista
                </a>
            </div>
        </div>

        <!-- TABLA PRINCIPAL DE FACTURAS -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/75 text-gray-500 text-[11px] uppercase tracking-wider border-b border-gray-100">
                        <th class="py-3.5 px-4 font-bold">Folio</th>
                        <th class="py-3.5 px-4 font-bold">RUT / Razón Social</th>
                        <th class="py-3.5 px-4 font-bold">Estado Pago</th>
                        <th class="py-3.5 px-4 font-bold">Recepción</th>
                        <th class="py-3.5 px-4 font-bold">Monto Total</th>
                        <th class="py-3.5 px-4 font-bold text-right pr-6">Fidelidad IA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($invoices as $invoice)                            
                        <tr onclick="window.location='{{ route('invoices.show', $invoice->id) }}'" 
                            class="hover:bg-orange-50/40 transition-colors cursor-pointer group">
                            
                            <!-- Folio -->
                            <td class="py-3.5 px-4 font-bold text-gray-900 group-hover:text-orange-600 transition-colors">
                                #{{ $invoice->folio }}
                                <span class="block text-[11px] text-gray-400 font-normal capitalize">{{ strtolower($invoice->document_type) }}</span>
                            </td>

                            <!-- RUT y Supplier -->
                            <td class="py-3.5 px-4 text-gray-600">
                                <div class="text-xs font-bold text-gray-800">{{ $invoice->rut }}</div>                                    
                                <div class="text-xs text-gray-500 font-medium truncate max-w-xs">{{ $invoice->supplier }}</div>
                            </td>

                            <!-- Estado de Pago Badge -->
                            <td class="py-3.5 px-4">
                                @if($invoice->payment_status === 'pagado')
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>PAGADO</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600"></i>
                                        <span>ADEUDADO</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Fecha Recepción -->
                            <td class="py-3.5 px-4 text-gray-600">
                                <div class="text-xs font-bold text-gray-700">{{ \Carbon\Carbon::parse($invoice->reception_date)->format('d-m-Y') }}</div>
                                <div class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($invoice->reception_date)->format('H:i:s') }}</div>
                            </td>

                            <!-- Monto Facturado y Abonos Parciales -->
                            <td class="py-3.5 px-4 font-semibold text-gray-900">
                                <div class="text-sm font-black text-gray-900">${{ number_format($invoice->amount, 0, ',', '.') }}</div>
                                @if($invoice->payment_status === 'adeudado' && $invoice->totalPaid() > 0)
                                    <div class="text-[11px] text-emerald-600 font-bold">
                                        Abonado: ${{ number_format($invoice->totalPaid(), 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>

                            <!-- Fidelidad IA -->
                            <td class="py-3.5 px-4 text-right pr-6">
                                @php
                                    $status = strtolower($invoice->fidelity);
                                @endphp

                                @if($invoice->is_reviewed)
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i data-lucide="circle-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>Vista</span>
                                    </span>
                                @elseif($status === 'baja')
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i data-lucide="gauge" class="w-3.5 h-3.5"></i>
                                        <span>Baja</span>
                                    </span>
                                @elseif($status === 'media')
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200">
                                        <i data-lucide="gauge" class="w-3.5 h-3.5"></i>
                                        <span>Media</span>
                                    </span>
                                @elseif($status === 'alta' || $status === 'fidelidad ok')
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        <i data-lucide="gauge" class="w-3.5 h-3.5"></i>
                                        <span>Alta</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                        {{ ucfirst($invoice->fidelity) }}
                                    </span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-gray-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i data-lucide="folder-open" class="w-10 h-10 text-gray-300"></i>
                                    <p class="text-sm font-medium text-gray-500">No hay documentos registrados o coincidentes con el filtro actual.</p>
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

</div>

@endsection