<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('customer_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();

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
            $table->decimal('valor_a_cuenta', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('total_contrato', 12, 2)->default(0);

            $table->string('plan_nombre')->nullable();
            $table->unsignedInteger('num_meses')->default(0);

            $table->enum('estado', ['borrador', 'confirmada', 'facturada', 'cancelada'])->default('borrador');
            $table->timestamps();
        });

        Schema::create('venta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->foreignId('paquete_id')->nullable()->constrained('paquetes')->nullOnDelete();
            $table->enum('tipo_item', ['equipo', 'paquete'])->default('equipo');
            $table->string('nombre');
            $table->string('modelo')->nullable();
            $table->string('marca')->nullable();
            $table->string('imagen')->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('sobreprecio', 12, 2)->default(0);
            $table->boolean('es_regalo')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->string('nombre');
            $table->date('fecha')->nullable();
            $table->decimal('porcentaje', 6, 2)->default(0);
            $table->decimal('monto', 12, 2)->default(0);
            $table->boolean('bloqueado')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('venta_ficha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('ficha_tecnica_id')->constrained('fichas_tecnicas')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_ficha');
        Schema::dropIfExists('venta_pagos');
        Schema::dropIfExists('venta_items');
        Schema::dropIfExists('ventas');
    }
};
