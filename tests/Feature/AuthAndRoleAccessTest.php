<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get('/scan')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/invoices/1')->assertRedirect('/login');
    }

    public function test_operator_can_access_scan_but_cannot_access_dashboard_or_invoices(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
        ]);
        $invoice = Invoice::factory()->create();

        $this->actingAs($operator)->get('/scan')->assertOk();
        $this->actingAs($operator)->get('/')->assertRedirect(route('capture.index'));
        $this->actingAs($operator)->get('/dashboard')->assertRedirect(route('capture.index'));
        $this->actingAs($operator)->get("/invoices/{$invoice->id}")->assertRedirect(route('capture.index'));
    }

    public function test_operator_receives_403_on_api_when_accessing_restricted_endpoints(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
        ]);
        $invoice = Invoice::factory()->create();

        $this->actingAs($operator)
            ->patchJson("/invoices/{$invoice->id}/review")
            ->assertStatus(403);
    }

    public function test_supervisor_and_admin_can_access_both_scan_and_dashboard(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'supervisor',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Supervisor
        $this->actingAs($supervisor)->get('/scan')->assertOk();
        $this->actingAs($supervisor)->get('/dashboard')->assertOk();
        $this->actingAs($supervisor)->get('/')->assertRedirect(route('dashboard'));

        // Admin
        $this->actingAs($admin)->get('/scan')->assertOk();
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_login_redirects_operator_to_scan_and_supervisor_to_dashboard(): void
    {
        $operator = User::factory()->create([
            'username' => 'operador1',
            'password' => 'secret123',
            'role' => 'operator',
        ]);

        $supervisor = User::factory()->create([
            'username' => 'supervisor1',
            'password' => 'secret123',
            'role' => 'supervisor',
        ]);

        // Login as operator
        $responseOp = $this->post('/login', [
            'username' => 'operador1',
            'password' => 'secret123',
        ]);
        $responseOp->assertRedirect(route('capture.index'));

        // Logout
        $this->post('/logout');

        // Login as supervisor
        $responseSup = $this->post('/login', [
            'username' => 'supervisor1',
            'password' => 'secret123',
        ]);
        $responseSup->assertRedirect('/dashboard');
    }
}
