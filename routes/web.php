<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CaptureController;

// Rutas de Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Panel Web principal y Operaciones de Facturas (Protegidos por autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Detalle de la factura (usando InvoiceController en lugar de DashboardController)
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    

    // Cambiar estado a revisada
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
});

// Ruta de prueba sin autenticación
Route::get('/probar-factura', [InvoiceController::class, 'processInvoice']);

// Ruta de capturador
Route::get('/scan', [CaptureController::class, 'index'])->name('capture.index');