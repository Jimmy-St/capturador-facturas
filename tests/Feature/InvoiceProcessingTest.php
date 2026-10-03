<?php

namespace Tests\Feature;

use App\Jobs\ProcessInvoiceOcr;
use App\Models\Invoice;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class InvoiceProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_invoice_dispatches_background_job_successfully(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('factura.jpg', 800, 1000);

        $response = $this->actingAs($user)->postJson(route('invoices.process'), [
            'image' => $file,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        Queue::assertPushed(ProcessInvoiceOcr::class);
    }

    public function test_process_invoice_ocr_job_saves_data_and_tokens(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();

        $tempPath = 'temp_invoices/test_image.jpg';
        $imageContent = UploadedFile::fake()->image('test.jpg', 800, 1000)->getContent();
        Storage::disk('local')->put($tempPath, $imageContent);

        /** @var GeminiService $geminiService */
        $geminiService = $this->mock(GeminiService::class, function (MockInterface $mock) {
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
                    'error' => '',
                    'tokens_cost' => 450,
                ]);
        });

        $job = new ProcessInvoiceOcr($tempPath, 'image/jpeg', $user->id);
        $job->handle($geminiService);

        $invoice = Invoice::where('folio', '123456')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('76.123.456-7', $invoice->rut);
        $this->assertEquals('EMPRESA PRUEBA SPA', $invoice->supplier);
        $this->assertEquals('2026-09-24', $invoice->document_date->format('Y-m-d'));
        $this->assertEquals(12345.67, (float) $invoice->amount);
        $this->assertEquals('alta', $invoice->fidelity);
        $this->assertEquals(450, $invoice->tokens_cost);
        $this->assertEquals($user->id, $invoice->user_id);

        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }

    public function test_process_invoice_ocr_job_parses_chilean_currency_without_corruption(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();

        $tempPath = 'temp_invoices/test_image2.jpg';
        $imageContent = UploadedFile::fake()->image('test2.jpg', 600, 800)->getContent();
        Storage::disk('local')->put($tempPath, $imageContent);

        /** @var GeminiService $geminiService */
        $geminiService = $this->mock(GeminiService::class, function (MockInterface $mock) {
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

        $job = new ProcessInvoiceOcr($tempPath, 'image/jpeg', $user->id);
        $job->handle($geminiService);

        $invoice = Invoice::where('folio', '999')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(15000.00, (float) $invoice->amount);
        $this->assertEquals('media', $invoice->fidelity);
        $this->assertEquals('2026-09-24', $invoice->document_date->format('Y-m-d'));
        $this->assertEquals(320, $invoice->tokens_cost);
    }

    public function test_process_invoice_validation_fails_for_non_image_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->postJson(route('invoices.process'), [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_demo_stores_image_with_uuid(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('scan_demo.jpg', 838, 1280);

        $response = $this->actingAs($user)->postJson(route('capture.demo.upload'), [
            'image' => $file,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'uuid',
                'file_name',
                'file_path',
                'file_url',
                'size_kb',
                'server_disk_time_ms',
            ]);

        $data = $response->json();
        Storage::disk('public')->assertExists($data['file_path']);
    }
}
