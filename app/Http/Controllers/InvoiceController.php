<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function __construct(protected GeminiService $geminiService) {}

    public function index(Request $request)
    {
        $query = Invoice::query();

        if ($request->filled('fidelity')) {
            $query->where('fidelity', $request->fidelity);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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
        $invoice = Invoice::with(['user', 'payments.user'])->find($id);

        return view('invoices.show', compact('invoice'));
    }

    // Método tradicional (por si se usa en otro lado)
    public function updateStatus(Invoice $invoice)
    {
        $invoice->update([
            'is_reviewed' => true,
        ]);

        return back()->with('success', 'Documento marcado como revisado exitosamente.');
    }

    // NUEVO: Método JSON para el botón AJAX de Alpine.js en la vista show
    public function markAsReviewed(Invoice $invoice): JsonResponse
    {
        $invoice->update([
            'is_reviewed' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Documento marcado como revisado exitosamente.',
        ]);
    }

    /**
     * Updates invoice operational data from the show form.
     *
     * @param  Request  $request  The incoming request with updated fields.
     * @param  Invoice  $invoice  The invoice instance to update.
     * @return RedirectResponse Redirects back with flash success message.
     */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($request->has('amount')) {
            $rawAmount = trim(str_replace([' ', '$'], '', (string) $request->input('amount')));
            if (str_contains($rawAmount, '.') && str_contains($rawAmount, ',')) {
                $rawAmount = str_replace('.', '', $rawAmount);
                $rawAmount = str_replace(',', '.', $rawAmount);
            } else {
                $rawAmount = str_replace(',', '.', $rawAmount);
            }
            $request->merge(['amount' => $rawAmount]);
        }

        $validated = $request->validate([
            'document_type' => 'required|string|max:255',
            'folio' => 'required|string|max:255',
            'rut' => 'required|string|max:255',
            'supplier' => 'required|string|max:255',
            'document_date' => 'required|date',
            'amount' => 'required|numeric',
        ]);

        $invoice->update($validated);

        return redirect()->back()->with('success', 'Documento actualizado correctamente.');
    }

    /**
     * Processes an uploaded invoice capture via synchronous OCR and persists the record.
     *
     * @param  Request  $request  The incoming HTTP request containing the captured image.
     * @return JsonResponse JSON payload with status, saved invoice summary, or validation error.
     */
    public function processInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:15360'],
        ], [
            'image.max' => 'La fotografía es demasiado pesada. Por favor, intenta de nuevo.',
            'image.image' => 'El archivo capturado no es una imagen válida.',
        ]);

        try {
            $file = $request->file('image');
            $mimeType = $file->getMimeType() ?: 'image/jpeg';
            $rawContent = $file->get();
            $optimizedImageContent = $this->optimizeInvoiceImage($rawContent);

            $ocrResponse = $this->geminiService->analyzeInvoiceFromContent($optimizedImageContent, $mimeType);

            if (! empty($ocrResponse['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $ocrResponse['error'],
                    'ocr_data' => $ocrResponse,
                ], 422);
            }

            $uuidName = (string) Str::uuid().'.jpg';
            $imagePath = 'invoices/'.$uuidName;
            Storage::disk('public')->put($imagePath, $optimizedImageContent);

            $invoice = Invoice::create([
                'document_type' => ! empty($ocrResponse['tipo_documento']) ? (string) $ocrResponse['tipo_documento'] : 'FACTURA ELECTRÓNICA',
                'folio' => ! empty($ocrResponse['numero_documento']) ? (string) $ocrResponse['numero_documento'] : 'S/N',
                'rut' => ! empty($ocrResponse['rut_proveedor']) ? (string) $ocrResponse['rut_proveedor'] : 'N/A',
                'supplier' => ! empty($ocrResponse['nombre_proveedor']) ? (string) $ocrResponse['nombre_proveedor'] : 'PROVEEDOR NO IDENTIFICADO',
                'document_date' => $this->parseDocumentDate($ocrResponse['fecha_emision'] ?? null),
                'reception_date' => now(),
                'amount' => $this->parseAmount($ocrResponse['total'] ?? 0),
                'fidelity' => $this->mapFidelity((int) ($ocrResponse['fidelidad_estimada'] ?? 0)),
                'tokens_cost' => (int) ($ocrResponse['tokens_cost'] ?? (($ocrResponse['tokens_in'] ?? 0) + ($ocrResponse['tokens_out'] ?? 0))),
                'payment_status' => 'adeudado',
                'image_path' => $imagePath,
                'raw_response_json' => json_encode($ocrResponse, JSON_UNESCAPED_UNICODE),
                'user_id' => (int) auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento procesado y registrado exitosamente.',
                'invoice' => [
                    'id' => $invoice->id,
                    'document_type' => $invoice->document_type,
                    'folio' => $invoice->folio,
                    'supplier' => $invoice->supplier,
                    'rut' => $invoice->rut,
                    'amount' => $invoice->amount,
                    'fidelity' => $invoice->fidelity,
                ],
                'ocr_data' => $ocrResponse,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el documento: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resizes invoice image only if dimensions exceed maximum boundaries to avoid redundant compression.
     *
     * @param  string  $rawContent  The raw binary image content.
     * @return string The original content or downscaled JPEG binary if resizing was necessary.
     */
    private function optimizeInvoiceImage(string $rawContent): string
    {
        $maxDimension = (int) config('invoices.max_dimension', 1530);
        $sizeInfo = @getimagesizefromstring($rawContent);

        if ($sizeInfo === false) {
            return $rawContent;
        }

        [$width, $height] = $sizeInfo;

        if ($width <= $maxDimension && $height <= $maxDimension) {
            return $rawContent;
        }

        $srcImage = @imagecreatefromstring($rawContent);
        if ($srcImage === false) {
            return $rawContent;
        }

        if ($width >= $height) {
            $newWidth = $maxDimension;
            $newHeight = (int) round(($height * $maxDimension) / $width);
        } else {
            $newHeight = $maxDimension;
            $newWidth = (int) round(($width * $maxDimension) / $height);
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($srcImage);

        $quality = (int) config('invoices.quality', 80);
        ob_start();
        imagejpeg($dstImage, null, $quality);
        $optimized = ob_get_clean();
        imagedestroy($dstImage);

        return $optimized ?: $rawContent;
    }

    /**
     * Parse amount safely without corrupting decimals or multiplying by 100.
     */
    private function parseAmount(mixed $amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        if (! is_string($amount)) {
            return 0.0;
        }

        $clean = trim(preg_replace('/[^\d.,]/', '', $amount));

        if ($clean === '') {
            return 0.0;
        }

        $hasDot = str_contains($clean, '.');
        $hasComma = str_contains($clean, ',');

        if ($hasDot && $hasComma) {
            $lastDot = strrpos($clean, '.');
            $lastComma = strrpos($clean, ',');

            if ($lastComma > $lastDot) {
                // Formato con miles de punto y coma decimal: 1.234.567,89
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // Formato anglosajón: 1,234,567.89
                $clean = str_replace(',', '', $clean);
            }

            return (float) $clean;
        }

        if ($hasComma) {
            $parts = explode(',', $clean);
            if (count($parts) === 2 && strlen($parts[1]) === 3 && strlen($parts[0]) <= 3) {
                return (float) str_replace(',', '', $clean);
            }

            return (float) str_replace(',', '.', $clean);
        }

        if ($hasDot) {
            $parts = explode('.', $clean);
            if (count($parts) > 2) {
                return (float) str_replace('.', '', $clean);
            }
            if (count($parts) === 2) {
                if (strlen($parts[1]) === 3 && strlen($parts[0]) <= 3) {
                    return (float) str_replace('.', '', $clean);
                }

                return (float) $clean;
            }
        }

        return (float) $clean;
    }

    /**
     * Parse document date safely into standard YYYY-MM-DD.
     */
    private function parseDocumentDate(mixed $date): string
    {
        if (empty($date)) {
            return now()->format('Y-m-d');
        }

        try {
            $normalizedDate = str_replace('/', '-', trim((string) $date));

            return Carbon::parse($normalizedDate)->format('Y-m-d');
        } catch (\Throwable) {
            return now()->format('Y-m-d');
        }
    }

    private function mapFidelity(int $percentage): string
    {
        if ($percentage >= 85) {
            return 'alta';
        }

        if ($percentage >= 50) {
            return 'media';
        }

        return 'baja';
    }
}
