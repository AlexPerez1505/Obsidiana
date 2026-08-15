<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->string('nombre');            // "Pago inicial", "Primer pago"...
            $table->date('fecha')->nullable();
            $table->decimal('porcentaje', 6, 2)->default(0);
            $table->decimal('monto', 12, 2)->default(0);
            $table->boolean('bloqueado')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_pagos');
    }
};
