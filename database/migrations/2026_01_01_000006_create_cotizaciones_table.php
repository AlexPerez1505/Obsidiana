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

            $table->unsignedBigInteger('cliente_id');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');

            $table->unsignedBigInteger('producto_id')->nullable();
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('set null');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuentos', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->string('lugar')->nullable();
            $table->decimal('costo_envio', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // FK real se agrega en una migración posterior (2026_01_01_000008)
            // porque plan_pagos todavía no existe en este punto (relación circular).
            $table->unsignedBigInteger('plan_pago_id')->nullable();

            $table->boolean('estado')->default(true);

            $table->unsignedBigInteger('paquete_id')->nullable();
            $table->foreign('paquete_id')->references('id')->on('paquetes')->onDelete('set null');

            $table->boolean('regalo')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
