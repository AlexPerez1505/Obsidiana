<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Pagos por aprobar: el vendedor sube el comprobante (o avisa que recibió
| efectivo) y queda pendiente; no cuenta para el saldo hasta que alguien
| con NIP o un administrador lo aprueba, y entonces nace el cobro real.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobro_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('venta_pago_id')->nullable()->constrained('venta_pagos')->nullOnDelete();
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->string('metodo', 20);
            $table->string('referencia')->nullable();
            $table->string('nota')->nullable();
            $table->json('archivos')->nullable();
            $table->string('estado', 12)->default('pendiente'); // pendiente | aprobado | rechazado
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('revisado_en')->nullable();
            $table->string('motivo_rechazo', 500)->nullable();
            $table->foreignId('cobro_id')->nullable()->constrained('cobros')->nullOnDelete();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobro_solicitudes');
    }
};
