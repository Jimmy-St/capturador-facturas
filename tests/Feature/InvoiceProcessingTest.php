<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class InvoiceProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_invoice_successfully_saves_data_and_tokens(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->mock(GeminiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('analyzeInvoiceFromContent')
                ->once()
                ->andReturn([
                    'tipo_documento' => 'FACTURA ELECTRÓNICA',
                    'numero_documento' => '123456',
                    'rut_proveedor' => '76.123.456-7',
                    'nombre_proveedor' => 'EMPRESA PRUEBA SPA',
                    'fecha_emision' => '2026-09-24',
                    'total' => '12345.67',
                    'articulos' => [
                        ['codigo' => 'ART1', 'descripcion' => 'Item de prueba'],
                    ],
                    'fidelidad_estimada' => 95,
                    'error' => 'none',
                    'tokens_cost' => 450,
                ]);
        });

        $file = UploadedFile::fake()->image('factura.jpg', 800, 1000);

        $response = $this->actingAs($user)->postJson(route('invoices.process'), [
            'image' => $file,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $invoice = Invoice::where('folio', '123456')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('76.123.456-7', $invoice->rut);
        $this->assertEquals('EMPRESA PRUEBA SPA', $invoice->supplier);
        $this->assertEquals('2026-09-24', $invoice->document_date->format('Y-m-d'));
        $this->assertEquals(12345.67, (float) $invoice->amount);
        $this->assertEquals('alta', $invoice->fidelity);
        $this->assertEquals(450, $invoice->tokens_cost);
        $this->assertEquals($user->id, $invoice->user_id);
    }

    public function test_process_invoice_parses_chilean_currency_without_corruption(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->mock(GeminiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('analyzeInvoiceFromContent')
                ->once()
                ->andReturn([
                    'tipo_documento' => 'FACTURA ELECTRÓNICA',
                    'numero_documento' => '999',
                    'rut_proveedor' => '76.555.444-3',
                    'nombre_proveedor' => 'PROVEEDOR CLP',
                    'fecha_emision' => '24/09/2026',
                    'total' => '$ 15.000',
                    'articulos' => [],
                    'fidelidad_estimada' => 70,
                    'error' => '',
                    'tokens_cost' => 320,
                ]);
        });

        $file = UploadedFile::fake()->image('factura2.jpg', 600, 800);

        $response = $this->actingAs($user)->postJson(route('invoices.process'), [
            'image' => $file,
        ]);

        $response->assertOk();

        $invoice = Invoice::where('folio', '999')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(15000.00, (float) $invoice->amount);
        $this->assertEquals('media', $invoice->fidelity);
        $this->assertEquals('2026-09-24', $invoice->document_date->format('Y-m-d'));
        $this->assertEquals(320, $invoice->tokens_cost);
    }

    public function test_process_invoice_returns_422_on_real_error(): void
    {
        $user = User::factory()->create();

        $this->mock(GeminiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('analyzeInvoiceFromContent')
                ->once()
                ->andReturn([
                    'tipo_documento' => '',
                    'numero_documento' => '',
                    'rut_proveedor' => '',
                    'nombre_proveedor' => '',
                    'fecha_emision' => '',
                    'total' => '0',
                    'articulos' => [],
                    'fidelidad_estimada' => 10,
                    'error' => 'La imagen no contiene un documento tributario legible.',
                    'tokens_cost' => 150,
                ]);
        });

        $file = UploadedFile::fake()->image('ilegible.jpg', 500, 500);

        $response = $this->actingAs($user)->postJson(route('invoices.process'), [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'La imagen no contiene un documento tributario legible.',
            ]);

        $this->assertDatabaseCount('invoices', 0);
    }
}
