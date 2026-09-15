<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class GeminiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));
        $this->model = 'gemini-3.5-flash-lite';
    }

    /**
     * Image from Laravel storage to structured JSON.
     * 
     * @param string $path
     * @param string $prompt
     * @return array
     */
    public function analizarFactura(string $path, string $prompt): array
    {        
        if (!Storage::disk('public')->exists($path)) {
            throw new Exception("La imagen no existe en la ruta especificada: {$path}");
        }

        // Image in Base64
        //$imageContent = Storage::get($path);
        //$mimeType = Storage::mimeType($path);
        $imageContent = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);
        $base64Image = base64_encode($imageContent);

        // URL endpoint Gemini
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        // JSON scheme
        $jsonSchema = [
            "type" => "OBJECT",
            "properties" => [
                "tipo_documento" => ["type" => "STRING"],
                "numero_documento" => ["type" => "STRING"],
                "rut_proveedor" => ["type" => "STRING"],
                "nombre_proveedor" => ["type" => "STRING"],
                "fecha_emision" => ["type" => "STRING"],
                "total" => ["type" => "STRING"],
                "articulos" => [
                    "type" => "ARRAY",
                    "items" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "codigo" => ["type" => "STRING"],
                            "descripcion" => ["type" => "STRING"]
                        ],
                        "required" => ["codigo", "descripcion"]
                    ]
                ],
                "fidelidad_estimada" => ["type" => "INTEGER"],
                "error" => ["type" => "STRING"]
            ],
            "required" => [
                "tipo_documento", "numero_documento", "rut_proveedor", 
                "nombre_proveedor", "fecha_emision", "total", 
                "articulos", "fidelidad_estimada", "error"
            ]
        ];

        // Payload HTTP
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt],
                        [
                            "inline_data" => [
                                "mime_type" => $mimeType,
                                "data" => $base64Image
                            ]
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "responseMimeType" => "application/json",
                "responseSchema" => $jsonSchema,
                "temperature" => 0.1 // Response temperature
            ]
        ];

        // HTTP get
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        // Errors control
        if ($response->failed()) {
            throw new Exception("Error al conectar con la API de Gemini: " . $response->body());
        }

        $responseData = $response->json();

        // Extract Gemini JSON response
        $jsonStringResponse = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$jsonStringResponse) {
            throw new Exception("La respuesta de Gemini no contiene el formato esperado.");
        }

        // JSON to Array
        return json_decode($jsonStringResponse, true);
    }
}