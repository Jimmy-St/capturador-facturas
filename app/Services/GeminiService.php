<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected string $apiKey;

    protected string $model;

    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-flash-latest'));
        $this->timeout = (int) config('services.gemini.timeout', env('GEMINI_TIMEOUT', 20));
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

        $response = Http::retry(3, 1000, function ($exception, $request) {
            return $exception instanceof RequestException
                && in_array($exception->response?->status(), [429, 500, 502, 503, 504]);
        })->timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();

            if ($status === 503 || str_contains($body, '503') || str_contains($body, 'UNAVAILABLE') || str_contains($body, 'high demand')) {
                throw new Exception('Los servidores de la lectura están experimentando una alta demanda temporal. Por favor vuelve a "Capturar Documento"');
            }

            if ($status === 429 || str_contains($body, 'RESOURCE_EXHAUSTED')) {
                throw new Exception('Límite de peticiones alcanzado en la API. Por favor espera un momento e intenta nuevamente.');
            }

            throw new Exception('Error al conectar con el servicio OCR: '.$body);
        }

        $responseData = $response->json();
        $jsonStringResponse = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! $jsonStringResponse) {
            throw new Exception('OCR response does not contain the expected format.');
        }

        $parsed = json_decode($jsonStringResponse, true);

        if (! is_array($parsed)) {
            throw new Exception('OCR response is not valid JSON.');
        }

        // Extraer consumo real de tokens desde usageMetadata en la respuesta raíz de Google
        $tokensCost = $responseData['usageMetadata']['totalTokenCount']
            ?? (($responseData['usageMetadata']['promptTokenCount'] ?? 0) + ($responseData['usageMetadata']['candidatesTokenCount'] ?? 0));

        $parsed['tokens_cost'] = (int) $tokensCost;

        return $parsed;
    }
}
