<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Invoice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);

        User::create([
            'username' => 'supervisor',
            'password' => Hash::make('12345678'),
            'role' => 'supervisor',
        ]);

        User::create([
            'username' => 'operador',
            'password' => Hash::make('12345678'),
            'role' => 'operator',
        ]);

        $faker = Faker::create('es_CL');

        $documentTypes = ['FACTURA', 'FACTURA ELECTRONICA', 'GUIA DE DESPACHO'];
        $fidelities = ['baja', 'media', 'alta'];
        
        $ruts = [
            '76.123.456-7', '76.543.210-K', '79.888.777-1', 
            '77.111.222-3', '75.333.444-5', '78.999.000-9'
        ];
        
        $sampleImages = ['invoices/invoice1.jpg', 'invoices/invoice2.jpg'];

        for ($i = 1; $i <= 50; $i++) {
            $folioNum = 1000 + $i;
            $daysAgo = rand(0, 30);
            $documentDate = Carbon::now()->subDays($daysAgo);
            
            Invoice::create([
                'document_type' => $documentTypes[array_rand($documentTypes)],
                'folio' => (string) $folioNum,
                'rut' => $ruts[array_rand($ruts)],
                'supplier' => $faker->company . ' ' . $faker->companySuffix,
                'document_date' => $documentDate,
                'reception_date' => (clone $documentDate)->addHours(rand(1, 5)),
                'amount' => rand(15, 850) * 1000,
                'fidelity' => $fidelities[array_rand($fidelities)],
                'tokens_cost' => rand(300, 1200),
                'is_reviewed' => (bool) rand(0, 1),
                'image_path' => $sampleImages[array_rand($sampleImages)],
                'raw_response_json' => json_encode([
                    'parser_status' => 'SUCCESS', 
                    'confidence' => rand(95, 99) + (rand(0, 9) / 10), 
                    'items_count' => rand(1, 5)
                ]),
                'user_id' => $admin->id,
            ]);
        }
    }
}