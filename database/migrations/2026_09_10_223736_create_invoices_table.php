<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('folio');
            $table->string('rut_emisor');
            $table->string('rut_receptor');
            $table->date('invoice_date');
            $table->dateTime('reception_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pendiente');
            
            // Nuevos campos operativos y de auditoría
            $table->string('image_path')->nullable();
            $table->longText('aws_response_json')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Para soft delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};