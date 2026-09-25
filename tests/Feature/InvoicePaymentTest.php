<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_payment_endpoints(): void
    {
        $invoice = Invoice::factory()->create();
        $payment = InvoicePayment::factory()->for($invoice)->create();

        $this->postJson(route('invoices.payments.store', $invoice), [])->assertUnauthorized();
        $this->deleteJson(route('invoices.payments.destroy', [$invoice, $payment]))->assertUnauthorized();
        $this->patchJson(route('invoices.payments.status', $invoice))->assertUnauthorized();
    }

    public function test_operator_is_forbidden_from_managing_payments(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $invoice = Invoice::factory()->create();
        $payment = InvoicePayment::factory()->for($invoice)->create();

        $this->actingAs($operator)
            ->postJson(route('invoices.payments.store', $invoice), [
                'amount' => 50000,
                'payment_date' => '2026-09-25',
                'image' => UploadedFile::fake()->image('cheque.jpg'),
            ])
            ->assertForbidden();

        $this->actingAs($operator)
            ->deleteJson(route('invoices.payments.destroy', [$invoice, $payment]))
            ->assertForbidden();

        $this->actingAs($operator)
            ->patchJson(route('invoices.payments.status', $invoice))
            ->assertForbidden();
    }

    public function test_supervisor_can_store_payment_with_proof_image(): void
    {
        Storage::fake('public');

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $invoice = Invoice::factory()->create([
            'amount' => 100000,
            'payment_status' => 'adeudado',
        ]);

        $file = UploadedFile::fake()->image('comprobante_cheque.jpg', 600, 400);

        $response = $this->actingAs($supervisor)
            ->postJson(route('invoices.payments.store', $invoice), [
                'amount' => 45000,
                'payment_date' => '2026-09-25',
                'payment_method' => 'cheque',
                'reference_number' => 'CHQ-889912',
                'notes' => 'Pago inicial con cheque al día',
                'image' => $file,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Pago registrado exitosamente.',
                'payment' => [
                    'amount' => 45000,
                    'payment_method' => 'cheque',
                    'payment_date' => '25-09-2026',
                    'reference_number' => 'CHQ-889912',
                    'notes' => 'Pago inicial con cheque al día',
                ],
                'total_paid' => 45000,
                'remaining_amount' => 55000,
                'is_fully_covered' => false,
            ]);

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id' => $invoice->id,
            'user_id' => $supervisor->id,
            'amount' => 45000,
            'reference_number' => 'CHQ-889912',
        ]);

        $payment = InvoicePayment::first();
        $this->assertNotNull($payment->image_path);
        Storage::disk('public')->assertExists($payment->image_path);
    }

    public function test_payment_validation_fails_with_invalid_data(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('invoices.payments.store', $invoice), [
                'amount' => -100,
                'payment_date' => 'invalid-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'payment_date', 'image']);
    }

    public function test_supervisor_can_delete_payment_and_file_is_physically_removed(): void
    {
        Storage::fake('public');

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $invoice = Invoice::factory()->create(['amount' => 50000]);

        $testImagePath = 'payments/test_cheque_to_delete.jpg';
        Storage::disk('public')->put($testImagePath, 'fake image binary content');

        $payment = InvoicePayment::factory()->for($invoice)->create([
            'user_id' => $supervisor->id,
            'amount' => 50000,
            'image_path' => $testImagePath,
        ]);

        Storage::disk('public')->assertExists($testImagePath);

        $response = $this->actingAs($supervisor)
            ->deleteJson(route('invoices.payments.destroy', [$invoice, $payment]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Pago anulado y comprobante eliminado.',
                'total_paid' => 0,
                'remaining_amount' => 50000,
                'is_fully_covered' => false,
            ]);

        $this->assertDatabaseMissing('invoice_payments', [
            'id' => $payment->id,
        ]);

        Storage::disk('public')->assertMissing($testImagePath);
    }

    public function test_cannot_delete_payment_belonging_to_another_invoice(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $invoice1 = Invoice::factory()->create();
        $invoice2 = Invoice::factory()->create();
        $payment = InvoicePayment::factory()->for($invoice1)->create();

        $response = $this->actingAs($supervisor)
            ->deleteJson(route('invoices.payments.destroy', [$invoice2, $payment]));

        $response->assertStatus(404);
        $this->assertDatabaseHas('invoice_payments', ['id' => $payment->id]);
    }

    public function test_toggle_payment_status_toggles_and_accepts_explicit_status(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $invoice = Invoice::factory()->create([
            'payment_status' => 'adeudado',
        ]);

        // Toggle from adeudado to pagado
        $response1 = $this->actingAs($supervisor)
            ->patchJson(route('invoices.payments.status', $invoice));

        $response1->assertOk()
            ->assertJson([
                'success' => true,
                'payment_status' => 'pagado',
                'is_paid' => true,
            ]);

        $this->assertEquals('pagado', $invoice->fresh()->payment_status);

        // Toggle from pagado to adeudado
        $response2 = $this->actingAs($supervisor)
            ->patchJson(route('invoices.payments.status', $invoice));

        $response2->assertOk()
            ->assertJson([
                'success' => true,
                'payment_status' => 'adeudado',
                'is_paid' => false,
            ]);

        $this->assertEquals('adeudado', $invoice->fresh()->payment_status);

        // Explicit set to pagado
        $response3 = $this->actingAs($supervisor)
            ->patchJson(route('invoices.payments.status', $invoice), [
                'status' => 'pagado',
            ]);

        $response3->assertOk()
            ->assertJson([
                'success' => true,
                'payment_status' => 'pagado',
            ]);

        $this->assertEquals('pagado', $invoice->fresh()->payment_status);
    }

    public function test_show_invoice_view_renders_correctly_with_payments_timeline(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $invoice = Invoice::factory()->create([
            'amount' => 150000,
            'payment_status' => 'adeudado',
        ]);

        $payment1 = InvoicePayment::factory()->for($invoice)->create([
            'user_id' => $supervisor->id,
            'amount' => 50000,
            'reference_number' => 'CHQ-111111',
            'notes' => 'Primer abono en cheque',
        ]);

        $payment2 = InvoicePayment::factory()->for($invoice)->create([
            'user_id' => $supervisor->id,
            'amount' => 100000,
            'reference_number' => 'TRF-222222',
            'notes' => 'Pago final por transferencia',
        ]);

        $response = $this->actingAs($supervisor)->get(route('invoices.show', $invoice));

        $response->assertOk()
            ->assertSee($invoice->folio)
            ->assertSee('CHQ-111111')
            ->assertSee('TRF-222222')
            ->assertSee('Primer abono en cheque')
            ->assertSee('Pago final por transferencia')
            ->assertSee('Registrar Pago')
            ->assertSee('Historial de Pagos');
    }

    public function test_dashboard_filters_invoices_by_payment_status(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $paidInvoice = Invoice::factory()->create([
            'folio' => 'FOLIO-PAGADO-100',
            'payment_status' => 'pagado',
        ]);

        $debtInvoice = Invoice::factory()->create([
            'folio' => 'FOLIO-ADEUDADO-200',
            'payment_status' => 'adeudado',
        ]);

        // Filter by adeudado
        $responseDebt = $this->actingAs($supervisor)
            ->get(route('dashboard', ['payment_status' => 'adeudado']));

        $responseDebt->assertOk()
            ->assertSee('FOLIO-ADEUDADO-200')
            ->assertDontSee('FOLIO-PAGADO-100');

        // Filter by pagado
        $responsePaid = $this->actingAs($supervisor)
            ->get(route('dashboard', ['payment_status' => 'pagado']));

        $responsePaid->assertOk()
            ->assertSee('FOLIO-PAGADO-100')
            ->assertDontSee('FOLIO-ADEUDADO-200');
    }
}
