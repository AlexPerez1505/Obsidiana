<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->integer('no_pago');

            $table->unsignedBigInteger('cliente_id');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');

            // La llave foranea hacia `cotizaciones` se agrega en
            // 2026_08_02_000030_add_cotizacion_foreign_to_plan_pagos_table.php,
            // porque esa tabla se crea despues (2026_08_02_000004).
            $table->unsignedBigInteger('cotizacion_id');

            $table->date('plazo_pagar');
            $table->string('metodo_pago')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_pagos');
    }
};
