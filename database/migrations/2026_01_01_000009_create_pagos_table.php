<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('plan_pago_id');
            $table->foreign('plan_pago_id')->references('id')->on('plan_pagos')->onDelete('cascade');

            $table->unsignedBigInteger('cliente_id');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');

            $table->decimal('monto_pagado', 12, 2)->default(0);
            $table->boolean('pago_atrasado')->default(false);
            $table->boolean('pagado')->default(false);

            // ✅ Normalizado: no_pago y metodo_pago YA NO se duplican aquí.
            // Se obtienen con JOIN a plan_pagos (plan_pago_id -> plan_pagos.no_pago / metodo_pago)

            $table->string('nota')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
