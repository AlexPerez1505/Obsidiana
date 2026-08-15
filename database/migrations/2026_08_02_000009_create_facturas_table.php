<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            // Datos fiscales (listos para timbrar en el futuro)
            $table->string('rfc')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('uso_cfdi')->nullable();
            $table->string('forma_pago')->nullable();
            $table->string('metodo_pago')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento_monto', 12, 2)->default(0);
            $table->decimal('envio', 12, 2)->default(0);
            $table->boolean('aplica_iva')->default(false);
            $table->decimal('iva_monto', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->enum('estado', ['borrador', 'emitida', 'cancelada'])->default('borrador');
            $table->timestamps();
        });

        Schema::create('factura_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('modelo')->nullable();
            $table->string('marca')->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('sobreprecio', 12, 2)->default(0);
            $table->boolean('es_regalo')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_items');
        Schema::dropIfExists('facturas');
    }
};
