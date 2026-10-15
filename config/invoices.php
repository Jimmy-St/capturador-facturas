<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Parámetros de Compresión y Calidad de Imágenes
    |--------------------------------------------------------------------------
    |
    | max_dimension: Dimensión máxima (ancho o alto) en píxeles para el escalado.
    | quality: Nivel de compresión JPEG en el servidor (1-100).
    | client_quality: Calidad de exportación inicial en el canvas del navegador.
    |
    */
    'target_width' => (int) env('INVOICE_IMAGE_TARGET_WIDTH', 760),
    'max_height' => (int) env('INVOICE_IMAGE_MAX_HEIGHT', 1530),
    'max_dimension' => (int) env('INVOICE_IMAGE_MAX_DIMENSION', 1530),
    'quality' => (int) env('INVOICE_IMAGE_QUALITY', 75),
    'client_quality' => (float) env('INVOICE_CLIENT_QUALITY', 0.85),
    'timeout' => (int) env('GEMINI_TIMEOUT', 20),
];
