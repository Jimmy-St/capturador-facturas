<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CaptureController extends Controller
{
    public function index()
    {
        return view('capture.index');
    }

    /**
     * Almacena la imagen de prueba en formato binario con UUID y calcula métricas de guardado en servidor.
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

        Storage::disk('public')->putFileAs($directory, $file, $fileName);

        $serverElapsedMs = round((microtime(true) - $serverStart) * 1000, 1);
        $sizeBytes = $file->getSize();

        return response()->json([
            'success' => true,
            'uuid' => $uuid,
            'file_name' => $fileName,
            'file_path' => $storedPath,
            'file_url' => Storage::disk('public')->url($storedPath),
            'size_kb' => round($sizeBytes / 1024, 1),
            'server_disk_time_ms' => $serverElapsedMs,
            'message' => 'Imagen almacenada exitosamente con UUID en el servidor.',
        ]);
    }
}
