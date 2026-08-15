<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('lugar_propuesta')->nullable();
            $table->text('nota_cliente')->nullable();

            $table->enum('modalidad', ['contado', 'financiamiento'])->default('contado');

            $table->boolean('aplica_iva')->default(false);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->enum('descuento_tipo', ['porcentaje', 'monto'])->nullable();
            $table->decimal('descuento_valor', 12, 2)->default(0);
            $table->decimal('descuento_monto', 12, 2)->default(0);
            $table->decimal('envio', 12, 2)->default(0);
            $table->decimal('iva_monto', 12, 2)->default(0);
            $table->decimal('valor_a_cuenta', 12, 2)->default(0); // trade-in / mercancía a cuenta
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('total_contrato', 12, 2)->default(0);

            $table->string('plan_nombre')->nullable();
            $table->unsignedInteger('num_meses')->default(0);

            $table->enum('estado', ['borrador', 'enviada', 'aceptada', 'rechazada', 'convertida'])->default('borrador');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
