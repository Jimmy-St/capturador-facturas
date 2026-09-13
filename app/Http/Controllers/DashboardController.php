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
        $status = $request->input('status');

        $query = Invoice::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('rut_emisor', 'like', "%{$search}%")
                  ->orWhere('rut_receptor', 'like', "%{$search}%")
                  ->orWhere('folio', 'like', "%{$search}%");
            });
        }

        if ($dateFrom && $dateTo) {
            $query->whereBetween('invoice_date', [$dateFrom, $dateTo]);
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Usamos el valor del .env (por defecto 20 si no existe)
        $perPage = env('PAGINATION_PER_PAGE', 20);
        $invoices = $query->latest()->paginate($perPage)->withQueryString();

        return view('dashboard', compact('invoices', 'search', 'dateFrom', 'dateTo', 'status'));
    }

    public function show($id)
    {
        $invoice = Invoice::findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }
}