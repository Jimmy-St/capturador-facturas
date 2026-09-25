<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoicePaymentController extends Controller
{
    /**
     * Store a newly created payment with its proof image.
     */
    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'image' => ['required', 'image', 'max:15360'],
        ], [
            'amount.required' => 'El monto del pago es obligatorio.',
            'amount.numeric' => 'El monto debe ser un valor numérico.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'payment_date.required' => 'La fecha de pago es obligatoria.',
            'payment_date.date' => 'La fecha de pago debe ser una fecha válida.',
            'image.required' => 'Debes adjuntar el comprobante o foto del cheque.',
            'image.image' => 'El comprobante debe ser un archivo de imagen válido.',
            'image.max' => 'La imagen del comprobante no puede superar los 15MB.',
        ]);

        $file = $request->file('image');
        $uuidName = (string) Str::uuid().'.jpg';
        $imagePath = 'payments/'.$uuidName;
        $imageContent = $file->get();
        $srcImage = @imagecreatefromstring($imageContent);

        $quality = (int) config('invoices.quality', 75);
        $maxDimension = (int) config('invoices.max_dimension', 1800);

        if ($srcImage !== false) {
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
            $compressedContent = ob_get_clean();
            imagedestroy($srcImage);
            Storage::disk('public')->put($imagePath, $compressedContent);
        } else {
            Storage::disk('public')->put($imagePath, $imageContent);
        }

        $payment = $invoice->payments()->create([
            'user_id' => auth()->id(),
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'] ?: 'cheque',
            'payment_date' => $validated['payment_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'image_path' => $imagePath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado exitosamente.',
            'payment' => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'formatted_amount' => number_format((float) $payment->amount, 0, ',', '.'),
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date->format('d-m-Y'),
                'raw_date' => $payment->payment_date->format('Y-m-d'),
                'reference_number' => $payment->reference_number,
                'image_url' => asset('storage/'.$payment->image_path),
                'notes' => $payment->notes,
                'user_name' => auth()->user()->username ?? 'Usuario',
                'created_at' => $payment->created_at->format('d-m-Y H:i'),
            ],
            'total_paid' => $invoice->totalPaid(),
            'remaining_amount' => $invoice->remainingAmount(),
            'is_fully_covered' => ($invoice->remainingAmount() <= 0.001),
            'payment_status' => $invoice->payment_status,
        ]);
    }

    /**
     * Remove the specified payment and physically delete its image proof (hard delete).
     */
    public function destroy(Invoice $invoice, InvoicePayment $payment): JsonResponse
    {
        if ($payment->invoice_id !== $invoice->id) {
            abort(404, 'El pago no pertenece a esta factura.');
        }

        if ($payment->image_path && Storage::disk('public')->exists($payment->image_path)) {
            Storage::disk('public')->delete($payment->image_path);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pago anulado y comprobante eliminado.',
            'total_paid' => $invoice->totalPaid(),
            'remaining_amount' => $invoice->remainingAmount(),
            'is_fully_covered' => ($invoice->remainingAmount() <= 0.001),
            'payment_status' => $invoice->payment_status,
        ]);
    }

    /**
     * Toggle or set the payment status of an invoice.
     */
    public function toggleStatus(Request $request, Invoice $invoice): JsonResponse
    {
        $newStatus = $request->input('status');
        if (! in_array($newStatus, ['adeudado', 'pagado'], true)) {
            $newStatus = ($invoice->payment_status === 'pagado') ? 'adeudado' : 'pagado';
        }

        $invoice->update([
            'payment_status' => $newStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => $newStatus === 'pagado' ? 'Documento marcado como PAGADO.' : 'Documento marcado como ADEUDADO.',
            'payment_status' => $newStatus,
            'is_paid' => ($newStatus === 'pagado'),
        ]);
    }
}
