<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

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
        $invoice = Invoice::with(['user', 'payments.user'])->findOrFail($id);

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

    // NUEVO: Método para actualizar los campos editables del formulario principal
    public function update(Request $request, Invoice $invoice)
    {
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

    public function processInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:15360'],
        ], [
            'image.max' => 'La fotografía es demasiado pesada. Por favor, intenta de nuevo.',
            'image.image' => 'El archivo capturado no es una imagen válida.',
        ]);

        $file = $request->file('image');
        $rawContent = $file->get();
        $prompt = config('services.gemini.prompt', env('GEMINI_PROMPT')) ?? '';

        // Pre-optimización del documento con GD para garantizar ultra-baja latencia y bajo consumo de tokens
        $optimizedImageContent = $this->optimizeInvoiceImage($rawContent);

        try {
            $response = $this->geminiService->analyzeInvoiceFromContent(
                $optimizedImageContent,
                'image/jpeg',
                $prompt
            );

            // Normalización del campo error para evitar falsos positivos
            $rawError = trim((string) ($response['error'] ?? ''));
            $ignorableErrors = ['', 'none', 'ninguno', 'null', 'n/a', 'no error', 'sin error', 'ok', 'no', 'false'];
            if ($rawError !== '' && ! in_array(mb_strtolower($rawError), $ignorableErrors, true)) {
                return response()->json([
                    'success' => false,
                    'error' => $rawError,
                ], 422);
            }

            $uuidName = (string) Str::uuid().'.jpg';
            $imagePath = 'invoices/'.$uuidName;
            Storage::disk('public')->put($imagePath, $optimizedImageContent);

            $invoice = Invoice::create([
                'document_type' => ! empty($response['tipo_documento']) ? (string) $response['tipo_documento'] : 'FACTURA ELECTRÓNICA',
                'folio' => ! empty($response['numero_documento']) ? (string) $response['numero_documento'] : 'S/N',
                'rut' => ! empty($response['rut_proveedor']) ? (string) $response['rut_proveedor'] : 'N/A',
                'supplier' => ! empty($response['nombre_proveedor']) ? (string) $response['nombre_proveedor'] : 'PROVEEDOR NO IDENTIFICADO',
                'document_date' => $this->parseDocumentDate($response['fecha_emision'] ?? null),
                'reception_date' => now(),
                'amount' => $this->parseAmount($response['total'] ?? 0),
                'fidelity' => $this->mapFidelity((int) ($response['fidelidad_estimada'] ?? 0)),
                'tokens_cost' => (int) ($response['tokens_cost'] ?? 0),
                'image_path' => $imagePath,
                'raw_response_json' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'is_operator' => auth()->user()?->isOperator() ?? false,
                'message' => 'Documento procesado exitosamente.',
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resize and compress invoice image for optimal OCR latency and storage efficiency.
     */
    private function optimizeInvoiceImage(string $rawContent): string
    {
        $maxDimension = (int) config('invoices.max_dimension', 1800);
        $quality = (int) config('invoices.quality', 75);

        $srcImage = @imagecreatefromstring($rawContent);
        if ($srcImage === false) {
            return $rawContent;
        }

        $width = imagesx($srcImage);
        $height = imagesy($srcImage);

        if ($width > $maxDimension || $height > $maxDimension) {
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
            $srcImage = $dstImage;
        }

        ob_start();
        imagejpeg($srcImage, null, $quality);
        $optimized = ob_get_clean();
        imagedestroy($srcImage);

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
