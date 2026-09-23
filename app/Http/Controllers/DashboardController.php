<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $fidelity = $request->input('fidelity'); // Cambiado de 'status' a 'fidelity'

        $query = Invoice::query();

        // 1. Filtro por búsqueda (Folio, Rut, Supplier)
        if ($search) {
            $query->where(function($q) use ($search) {
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

        $perPage = env('PAGINATION_PER_PAGE', 40);
        $invoices = $query->latest()->paginate($perPage)->withQueryString();

        return view('dashboard', compact('invoices', 'search', 'dateFrom', 'dateTo', 'fidelity'));
    }

    public function show($id)
    {
        $invoice = Invoice::findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }
}