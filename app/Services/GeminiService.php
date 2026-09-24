<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-3.5-flash-lite'));
    }

    /**
     * Analyze invoice image content using Gemini API and return structured data including token usage.
     *
     * @param  string  $imageContent  Raw binary content of the image
     * @param  string  $mimeType  Mime type of the image (e.g. image/jpeg)
     * @param  string  $prompt  Prompt instructions for extraction
     *
     * @throws Exception
     */
    public function analyzeInvoiceFromContent(string $imageContent, string $mimeType, string $prompt = ''): array
    {
        $base64Image = base64_encode($imageContent);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $effectivePrompt = ! empty(trim($prompt))
            ? $prompt
            : 'Analiza detalladamente esta imagen de documento tributario y extrae con máxima precisión todos los campos requeridos en el esquema JSON.';

        $jsonSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'tipo_documento' => ['type' => 'STRING'],
                'numero_documento' => ['type' => 'STRING'],
                'rut_proveedor' => ['type' => 'STRING'],
                'nombre_proveedor' => ['type' => 'STRING'],
                'fecha_emision' => ['type' => 'STRING'],
                'total' => ['type' => 'STRING'],
                'articulos' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'codigo' => ['type' => 'STRING'],
                            'descripcion' => ['type' => 'STRING'],
                        ],
                        'required' => ['codigo', 'descripcion'],
                    ],
                ],
                'fidelidad_estimada' => ['type' => 'INTEGER'],
                'error' => ['type' => 'STRING'],
            ],
            'required' => [
                'tipo_documento', 'numero_documento', 'rut_proveedor',
                'nombre_proveedor', 'fecha_emision', 'total',
                'articulos', 'fidelidad_estimada', 'error',
            ],
        ];

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $effectivePrompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $jsonSchema,
                'temperature' => 0.1,
            ],
        ];

        $response = Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

        if ($response->failed()) {
            throw new Exception('Gemini API connection error: '.$response->body());
        }

        $responseData = $response->json();
        $jsonStringResponse = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! $jsonStringResponse) {
            throw new Exception('Gemini response does not contain the expected format.');
        }

        $parsed = json_decode($jsonStringResponse, true);

        if (! is_array($parsed)) {
            throw new Exception('Gemini response is not valid JSON.');
        }

        // Extraer consumo real de tokens desde usageMetadata en la respuesta raíz de Google
        $tokensCost = $responseData['usageMetadata']['totalTokenCount']
            ?? (($responseData['usageMetadata']['promptTokenCount'] ?? 0) + ($responseData['usageMetadata']['candidatesTokenCount'] ?? 0));

        $parsed['tokens_cost'] = (int) $tokensCost;

        return $parsed;
    }
}
