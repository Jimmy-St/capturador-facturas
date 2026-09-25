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
            $table->string('document_type')->default('FACTURA');
            $table->string('folio');
            $table->string('rut');
            $table->string('supplier');
            $table->date('document_date');
            $table->dateTime('reception_date')->nullable();
            $table->decimal('amount', 12, 2);

            $table->string('fidelity')->default('media');

            $table->unsignedInteger('tokens_cost')->default(0);

            $table->boolean('is_reviewed')->default(false);
            $table->string('payment_status')->default('adeudado')->index();

            $table->string('image_path')->nullable();
            $table->longText('raw_response_json')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
