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
    /**
     * Muestra la vista detallada de una factura específica.
     */
    public function show($id)
    {
        $invoice = Invoice::with('user')->findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Actualiza el estado de la factura a 'revisada'.
     */
    public function updateStatus(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'revisada'
        ]);

        return back()->with('success', 'Factura marcada como revisada exitosamente.');
    }

    public function processInvoice(GeminiService $geminiService): JsonResponse
    {
        //$path = 'invoices/invoice1.jpg'; 
        $path = 'invoices/invoice2.jpg'; 
        
        $prompt = 'Analiza la imagen adjunta. Verifica que corresponda a un documento tributario (factura, guía, boleta, etc.) Si la imagen muestra otra cosa, devuelve "error": "imagen incorrecta". Si la imagen está ilegible, borrosa o muy oscura, devuelve: "error":  "imagen borrosa" o "error": "imagen oscura". Si el documento es válido y legible, extrae estrictamente los campos requeridos, la fecha como año-mes-dia y deja el campo error vacío.';

        try {
            $resultadoJson = $geminiService->analizarFactura($path, $prompt);
            return response()->json(['success' => true,'data' => $resultadoJson]);

        } catch (\Exception $e) {
            return response()->json(['success' => false,'error' => $e->getMessage()], 500);
        }
    }
}