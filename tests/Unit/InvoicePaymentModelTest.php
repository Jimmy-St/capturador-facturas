<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePaymentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_payment_belongs_to_invoice_and_user(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create();

        $payment = InvoicePayment::factory()->create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 50000.00,
        ]);

        $this->assertEquals($invoice->id, $payment->invoice->id);
        $this->assertEquals($user->id, $payment->user->id);
    }

    public function test_invoice_calculates_total_paid_and_remaining_amount(): void
    {
        $invoice = Invoice::factory()->create([
            'amount' => 100000.00,
            'payment_status' => 'adeudado',
        ]);

        $this->assertEquals(0.0, $invoice->totalPaid());
        $this->assertEquals(100000.0, $invoice->remainingAmount());
        $this->assertTrue($invoice->isDebt());
        $this->assertFalse($invoice->isPaid());

        // Primer abono: 40.000
        InvoicePayment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 40000.00,
        ]);

        $this->assertEquals(40000.0, $invoice->totalPaid());
        $this->assertEquals(60000.0, $invoice->remainingAmount());

        // Segundo abono: 60.000
        InvoicePayment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 60000.00,
        ]);

        $this->assertEquals(100000.0, $invoice->totalPaid());
        $this->assertEquals(0.0, $invoice->remainingAmount());
    }
}
