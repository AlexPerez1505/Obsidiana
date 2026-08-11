<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_pago_plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('numero_pagos');
            $table->unsignedInteger('dias_entre_pagos');
            $table->string('metodo_pago')->default('Efectivo');
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_pago_plantillas');
    }
};
