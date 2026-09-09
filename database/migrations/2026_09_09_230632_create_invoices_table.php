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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Quién subió la factura
            $table->string('rut_proveedor')->nullable();
            $table->string('folio')->nullable();
            $table->date('fecha_emision')->nullable();
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->json('items')->nullable(); // Bloque estilo NoSQL para los productos
            $table->string('estado')->default('pendiente'); // 'aprobado', 'por_revisar' (ícono rojo)
            $table->text('imagen_path'); // Ruta de la foto original guardada
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};