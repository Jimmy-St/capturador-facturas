<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePaymentController;
use Illuminate\Support\Facades\Route;

// Rutas de Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas Protegidas por Autenticación
Route::middleware('auth')->group(function () {
    // Redirección inteligente de raíz según el rol
    Route::get('/', function () {
        return auth()->user()->isOperator()
            ? redirect()->route('capture.index')
            : redirect()->route('dashboard');
    });

    // Módulo de Captura en Terreno y Procesamiento (Accesible por operator, supervisor y admin)
    Route::get('/scan', [CaptureController::class, 'index'])->name('capture.index');
    Route::view('/scan-demo', 'capture.demo')->name('capture.demo');
    Route::post('/invoices/process', [InvoiceController::class, 'processInvoice'])->name('invoices.process');

    // Panel de Auditoría y Dashboard (Solo supervisor y admin)
    Route::middleware('role:supervisor,admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Detalle, edición y revisión de facturas
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::patch('/invoices/{invoice}/review', [InvoiceController::class, 'markAsReviewed'])->name('invoices.review');
        Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');

        // Gestión de pagos de facturas
        Route::post('/invoices/{invoice}/payments', [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
        Route::delete('/invoices/{invoice}/payments/{payment}', [InvoicePaymentController::class, 'destroy'])->name('invoices.payments.destroy');
        Route::patch('/invoices/{invoice}/payment-status', [InvoicePaymentController::class, 'toggleStatus'])->name('invoices.payments.status');
    });
});
