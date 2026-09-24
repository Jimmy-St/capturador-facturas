<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => 'FACTURA ELECTRÓNICA',
            'folio' => (string) fake()->numberBetween(1000, 99999),
            'rut' => '76.123.456-7',
            'supplier' => fake()->company(),
            'document_date' => now()->subDays(2),
            'reception_date' => now(),
            'amount' => 150000.00,
            'fidelity' => 'alta',
            'tokens_cost' => 500,
            'is_reviewed' => false,
            'image_path' => 'invoices/sample.jpg',
            'raw_response_json' => json_encode(['status' => 'OK']),
        ];
    }
}
