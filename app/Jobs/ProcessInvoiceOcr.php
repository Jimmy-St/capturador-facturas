<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessInvoiceOcr implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public string $tempPath,
        public string $mimeType,
        public int $userId
    ) {}

    public function handle(GeminiService $geminiService): void
    {
        if (! Storage::disk('local')->exists($this->tempPath)) {
            Log::warning("ProcessInvoiceOcr: Temporary file {$this->tempPath} not found.");

            return;
        }

        try {
            $rawContent = Storage::disk('local')->get($this->tempPath);
            $optimizedImageContent = $this->optimizeInvoiceImage($rawContent);

            $response = $geminiService->analyzeInvoiceFromContent(
                $optimizedImageContent,
                $this->mimeType
            );

            if (! empty($response['error'])) {
                Log::warning("ProcessInvoiceOcr: OCR returned error '{$response['error']}' for user {$this->userId}.");
                Storage::disk('local')->delete($this->tempPath);

                return;
            }

            $uuidName = (string) Str::uuid().'.jpg';
            $imagePath = 'invoices/'.$uuidName;
            Storage::disk('public')->put($imagePath, $optimizedImageContent);

            Invoice::create([
                'document_type' => ! empty($response['tipo_documento']) ? (string) $response['tipo_documento'] : 'FACTURA ELECTRÓNICA',
                'folio' => ! empty($response['numero_documento']) ? (string) $response['numero_documento'] : 'S/N',
                'rut' => ! empty($response['rut_proveedor']) ? (string) $response['rut_proveedor'] : 'N/A',
                'supplier' => ! empty($response['nombre_proveedor']) ? (string) $response['nombre_proveedor'] : 'PROVEEDOR NO IDENTIFICADO',
                'document_date' => $this->parseDocumentDate($response['fecha_emision'] ?? null),
                'reception_date' => now(),
                'amount' => $this->parseAmount($response['total'] ?? 0),
                'fidelity' => $this->mapFidelity((int) ($response['fidelidad_estimada'] ?? 0)),
                'tokens_cost' => (int) ($response['tokens_cost'] ?? 0),
                'payment_status' => 'adeudado',
                'image_path' => $imagePath,
                'raw_response_json' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'user_id' => $this->userId,
            ]);

            Storage::disk('local')->delete($this->tempPath);

        } catch (\Throwable $e) {
            Log::error("ProcessInvoiceOcr failed: {$e->getMessage()}", [
                'tempPath' => $this->tempPath,
                'userId' => $this->userId,
            ]);

            throw $e;
        }
    }

    /**
     * Clean up temp file if job fails permanently after all retries.
     */
    public function failed(\Throwable $exception): void
    {
        if (Storage::disk('local')->exists($this->tempPath)) {
            Storage::disk('local')->delete($this->tempPath);
        }
    }

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
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
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
