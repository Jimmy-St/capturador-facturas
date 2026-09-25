<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('12345678'),
                'role' => 'admin',
            ]
        );

        $supervisor = User::firstOrCreate(
            ['username' => 'supervisor'],
            [
                'password' => Hash::make('12345678'),
                'role' => 'supervisor',
            ]
        );

        User::firstOrCreate(
            ['username' => 'operador'],
            [
                'password' => Hash::make('12345678'),
                'role' => 'operator',
            ]
        );

        $faker = Faker::create('es_CL');

        $documentTypes = ['FACTURA', 'FACTURA ELECTRONICA', 'GUIA DE DESPACHO'];
        $fidelities = ['baja', 'media', 'alta'];

        $ruts = [
            '76.123.456-7', '76.543.210-K', '79.888.777-1',
            '77.111.222-3', '75.333.444-5', '78.999.000-9',
        ];

        $sampleImages = ['invoices/invoice1.jpg', 'invoices/invoice2.jpg'];

        for ($i = 1; $i <= 50; $i++) {
            $folioNum = 1000 + $i;
            $daysAgo = rand(0, 30);
            $documentDate = Carbon::now()->subDays($daysAgo);
            $amount = rand(15, 850) * 1000;

            // Determinar estado de pago: ~35% pagado, ~65% adeudado
            $isPaid = ($i % 3 === 0);
            $paymentStatus = $isPaid ? 'pagado' : 'adeudado';

            $invoice = Invoice::create([
                'document_type' => $documentTypes[array_rand($documentTypes)],
                'folio' => (string) $folioNum,
                'rut' => $ruts[array_rand($ruts)],
                'supplier' => $faker->company.' '.$faker->companySuffix,
                'document_date' => $documentDate,
                'reception_date' => (clone $documentDate)->addHours(rand(1, 5)),
                'amount' => $amount,
                'fidelity' => $fidelities[array_rand($fidelities)],
                'tokens_cost' => rand(300, 1200),
                'is_reviewed' => (bool) rand(0, 1),
                'payment_status' => $paymentStatus,
                'image_path' => $sampleImages[array_rand($sampleImages)],
                'raw_response_json' => json_encode([
                    'parser_status' => 'SUCCESS',
                    'confidence' => rand(95, 99) + (rand(0, 9) / 10),
                    'items_count' => rand(1, 5),
                ]),
                'user_id' => $admin->id,
            ]);

            // Si está pagada, crear 1 o 2 pagos asociados
            if ($isPaid) {
                $payDate = (clone $invoice->reception_date)->addDays(rand(1, 5));
                $methods = ['cheque', 'transferencia'];
                $method = $methods[array_rand($methods)];

                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => (rand(0, 1) ? $admin->id : $supervisor->id),
                    'amount' => $amount,
                    'payment_method' => $method,
                    'payment_date' => $payDate,
                    'reference_number' => $method === 'cheque' ? 'CHQ-'.rand(100000, 999999) : 'TRF-'.rand(1000000, 9999999),
                    'image_path' => 'payments/sample_check.jpg',
                    'notes' => 'Pago total registrado conforme.',
                ]);
            } elseif ($i % 5 === 0) {
                // Pago parcial para algunas facturas adeudadas
                $partialAmount = round($amount / 2, 2);
                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => $supervisor->id,
                    'amount' => $partialAmount,
                    'payment_method' => 'cheque',
                    'payment_date' => (clone $invoice->reception_date)->addDays(1),
                    'reference_number' => 'CHQ-'.rand(100000, 999999),
                    'image_path' => 'payments/sample_check.jpg',
                    'notes' => 'Abono 50% inicial con cheque a 30 días.',
                ]);
            }
        }
    }
}
