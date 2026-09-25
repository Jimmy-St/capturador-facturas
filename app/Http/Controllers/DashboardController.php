<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $fidelity = $request->input('fidelity');
        $paymentStatus = $request->input('payment_status');

        $query = Invoice::with(['user', 'payments']);

        // 1. Filtro por búsqueda (Folio, Rut, Supplier)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('rut', 'like', "%{$search}%")
                    ->orWhere('supplier', 'like', "%{$search}%")
                    ->orWhere('folio', 'like', "%{$search}%");
            });
        }

        // 2. Filtro por rango de fechas
        if ($dateFrom && $dateTo) {
            $query->whereBetween('document_date', [$dateFrom, $dateTo]);
        }

        // 3. Filtro por Fidelidad o Estado Vista
        if ($fidelity) {
            if ($fidelity === 'vista') {
                // Si el filtro es 'vista', filtramos por los documentos ya revisados
                $query->where('is_reviewed', true);
            } else {
                // Si es baja, media o alta, filtramos por el campo fidelity
                $query->where('fidelity', strtolower($fidelity));
            }
        }

        // 4. Filtro por Estado de Pago (adeudado / pagado)
        if ($paymentStatus && in_array($paymentStatus, ['adeudado', 'pagado'], true)) {
            $query->where('payment_status', $paymentStatus);
        }

        // Métricas globales para las tarjetas del Dashboard
        $stats = [
            'total_count' => Invoice::count(),
            'adeudado_count' => Invoice::where('payment_status', 'adeudado')->count(),
            'pagado_count' => Invoice::where('payment_status', 'pagado')->count(),
            'total_amount_adeudado' => (float) Invoice::where('payment_status', 'adeudado')->sum('amount'),
            'total_amount_pagado' => (float) Invoice::where('payment_status', 'pagado')->sum('amount'),
        ];

        $perPage = (int) env('PAGINATION_PER_PAGE', 40);
        $invoices = $query->latest('reception_date')->paginate($perPage)->withQueryString();

        return view('dashboard', compact(
            'invoices', 'search', 'dateFrom', 'dateTo', 'fidelity', 'paymentStatus', 'stats'
        ));
    }

    public function show($id)
    {
        $invoice = Invoice::findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }
}
