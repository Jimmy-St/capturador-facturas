<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function index(Request $request)
    {
        $query = Invoice::query();

        if ($request->filled('fidelity')) {
            $query->where('fidelity', $request->fidelity);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                  ->orWhere('rut', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%");
            });
        }

        $invoices = $query->latest('reception_date')->paginate(10)->withQueryString();

        return view('dashboard', compact('invoices'));
    }

    public function show($id)
    {
        $invoice = Invoice::with('user')->find($id);

        return view('invoices.show', compact('invoice'));
    }

    // Método tradicional (por si se usa en otro lado)
    public function updateStatus(Invoice $invoice)
    {
        $invoice->update([
            'is_reviewed' => true
        ]);

        return back()->with('success', 'Documento marcado como revisado exitosamente.');
    }

    // NUEVO: Método JSON para el botón AJAX de Alpine.js en la vista show
    public function markAsReviewed(Invoice $invoice): JsonResponse
    {
        $invoice->update([
            'is_reviewed' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Documento marcado como revisado exitosamente.'
        ]);
    }

    // NUEVO: Método para actualizar los campos editables del formulario principal
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:255',
            'folio'         => 'required|string|max:255',
            'rut'           => 'required|string|max:255',
            'supplier'      => 'required|string|max:255',
            'document_date' => 'required|date',
            'amount'        => 'required|numeric',
        ]);

        $invoice->update($validated);

        return redirect()->back()->with('success', 'Documento actualizado correctamente.');
    }

    public function processInvoice(GeminiService $geminiService): JsonResponse
    {
        $path = 'invoices/invoice2.jpg'; 
        $prompt = config('services.gemini.prompt', env('GEMINI_PROMPT'));
        
        try {
            $resultadoJson = $geminiService->analizarFactura($path, $prompt);
            return response()->json(['success' => true, 'data' => $resultadoJson]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}