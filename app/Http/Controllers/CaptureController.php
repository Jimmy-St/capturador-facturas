<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CaptureController extends Controller
{
    public function __construct(protected GeminiService $geminiService) {}

    public function index()
    {
        return view('capture.index');
    }

    /**
     * Almacena la imagen de prueba en formato binario con UUID, la analiza con Gemini OCR y calcula métricas detalladas.
     */
    public function uploadDemo(Request $request): JsonResponse
    {
        $serverStart = microtime(true);

        $request->validate([
            'image' => ['required', 'image', 'max:15360'],
        ]);

        $file = $request->file('image');
        $uuid = (string) Str::uuid();
        $fileName = $uuid.'.jpg';
        $directory = 'demo_scans';
        $storedPath = $directory.'/'.$fileName;

        // 1. Escritura física en disco del servidor
        $diskStart = microtime(true);
        Storage::disk('public')->putFileAs($directory, $file, $fileName);
        $serverDiskTimeMs = round((microtime(true) - $diskStart) * 1000, 1);

        $sizeBytes = $file->getSize();
        $fileContent = $file->get();

        // 2. Envío y análisis con la API de Gemini (mismo modelo configurado)
        $geminiStart = microtime(true);
        try {
            $ocrResponse = $this->geminiService->analyzeInvoiceFromContent($fileContent, 'image/jpeg');
            $geminiTimeMs = round((microtime(true) - $geminiStart) * 1000, 1);
            $ocrSuccess = true;
            $ocrError = null;
        } catch (\Throwable $e) {
            $geminiTimeMs = round((microtime(true) - $geminiStart) * 1000, 1);
            $ocrSuccess = false;
            $ocrError = $e->getMessage();
            $ocrResponse = null;
        }

        $totalServerElapsedMs = round((microtime(true) - $serverStart) * 1000, 1);

        return response()->json([
            'success' => true,
            'uuid' => $uuid,
            'file_name' => $fileName,
            'file_path' => $storedPath,
            'file_url' => Storage::disk('public')->url($storedPath),
            'size_kb' => round($sizeBytes / 1024, 1),
            'server_disk_time_ms' => $serverDiskTimeMs,
            'gemini_time_ms' => $geminiTimeMs,
            'server_total_time_ms' => $totalServerElapsedMs,
            'ocr_success' => $ocrSuccess,
            'ocr_error' => $ocrError,
            'ocr_data' => $ocrResponse,
            'message' => 'Imagen procesada y analizada correctamente.',
        ]);
    }
}
