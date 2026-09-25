<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoicePayment>
 */
class InvoicePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'user_id' => User::factory(),
            'amount' => 50000.00,
            'payment_method' => 'cheque',
            'payment_date' => now(),
            'reference_number' => 'CHQ-'.fake()->numerify('######'),
            'image_path' => 'payments/sample_check.jpg',
            'notes' => fake()->sentence(),
        ];
    }
}
