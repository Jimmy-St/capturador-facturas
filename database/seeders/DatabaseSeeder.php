<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Invoice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear el usuario administrador oficial
        $admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);

        $supervisor = User::create([
            'username' => 'supervisor',
            'password' => Hash::make('12345678'),
            'role' => 'supervisor',
        ]);

        $operator = User::create([
            'username' => 'operador',
            'password' => Hash::make('12345678'),
            'role' => 'operator',
        ]);

        // 2. Generar 50 registros de facturas de prueba
        $statuses = ['pendiente', 'fidelidad OK', 'revisada'];
        $rutEmisores = [
            '76.123.456-7', '76.543.210-K', '79.888.777-1', 
            '77.111.222-3', '75.333.444-5', '78.999.000-9'
        ];
        $rutReceptores = [
            '77.987.654-3', '78.111.222-3', '76.123.456-7', 
            '79.555.444-3', '75.111.999-K'
        ];
        
        // Imágenes reales dispuestas en la carpeta storage/app/public/invoices/
        $sampleImages = ['invoices/invoice1.jpg', 'invoices/invoice2.jpg'];

        for ($i = 1; $i <= 50; $i++) {
            $folioNum = 1000 + $i;
            $daysAgo = rand(0, 30);
            $invoiceDate = Carbon::now()->subDays($daysAgo);
            
            Invoice::create([
                'folio' => (string) $folioNum,
                'rut_emisor' => $rutEmisores[array_rand($rutEmisores)],
                'rut_receptor' => $rutReceptores[array_rand($rutReceptores)],
                'invoice_date' => $invoiceDate,
                'reception_date' => (clone $invoiceDate)->addHours(rand(1, 5)),
                'amount' => rand(15, 850) * 1000,
                'status' => $statuses[array_rand($statuses)],
                'image_path' => $sampleImages[array_rand($sampleImages)], // Asignación aleatoria de invoice1 o invoice2
                'aws_response_json' => json_encode([
                    'textract_status' => 'SUCCESS', 
                    'confidence' => rand(95, 99) + (rand(0, 9) / 10), 
                    'document_type' => 'ELECTRONIC_INVOICE',
                    'items_count' => rand(1, 5)
                ]),
                'user_id' => $admin->id,
            ]);
        }
    }
}