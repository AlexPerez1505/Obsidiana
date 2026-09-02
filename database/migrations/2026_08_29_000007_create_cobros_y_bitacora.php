<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cobros: el dinero que de verdad entró.
 *
 * Hasta ahora venta_pagos guardaba el PLAN (qué se va a pagar y cuándo),
 * pero no había dónde registrar lo que el cliente ya pagó. Sin eso no se
 * puede saber el saldo, ni emitir recibos, ni proteger un abono cuando se
 * edita la venta.
 *
 * Un cobro puede aplicarse a una parcialidad del plan o ir suelto (un
 * abono adelantado, por ejemplo), de ahí que venta_pago_id sea opcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('venta_pago_id')->nullable()->constrained('venta_pagos')->nullOnDelete();

            $table->string('folio')->unique();
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->string('metodo', 30);          // transferencia, efectivo, tarjeta...
            $table->string('referencia')->nullable(); // folio bancario, últimos 4 dígitos
            $table->string('nota')->nullable();

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['venta_id', 'fecha']);
        });

        // Comprobantes que sube quien registra el cobro.
        Schema::create('cobro_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobro_id')->constrained('cobros')->cascadeOnDelete();
            $table->string('archivo');
            $table->string('nombre')->nullable();
            $table->timestamps();
        });

        /*
        | Bitácora de la venta: quién movió una fecha, cambió un monto o
        | agregó equipo. Sin esto, editar una venta ya cobrada es un acto de
        | fe; con esto queda el rastro.
        */
        Schema::create('venta_bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 40);        // fechas_recorridas, plan_editado, items_editados...
            $table->string('descripcion');
            $table->json('datos')->nullable(); // antes / después
            $table->timestamps();

            $table->index(['venta_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_bitacora');
        Schema::dropIfExists('cobro_evidencias');
        Schema::dropIfExists('cobros');
    }
};
