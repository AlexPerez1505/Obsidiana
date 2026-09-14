<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comisiones de los asesores.
 *
 * El porcentaje vive en el usuario (cada asesor puede tener el suyo) y
 * cada pago de comisión queda como su propio renglón, ligado al periodo
 * (mes) que cubre, para saber cuánto se le ha pagado ya a cada quien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('porcentaje_comision', 5, 2)->nullable()->after('payroll_number');
        });

        Schema::create('comision_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Mes que cubre el pago, como "2026-09".
            $table->char('periodo', 7)->index();
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            // Sobre qué se calculó al momento de pagar: vendido o cobrado.
            $table->string('base', 10)->default('cobrado');
            $table->string('nota', 255)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_pagos');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('porcentaje_comision');
        });
    }
};
