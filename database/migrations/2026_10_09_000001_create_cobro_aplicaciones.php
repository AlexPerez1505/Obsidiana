<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un cobro se queda como UN solo recibo por lo que el cliente pagó, y esta
 * tabla dice cuánto de ese dinero se aplicó a cada pago del calendario.
 *
 * Ejemplo: tres pagos de $533 y un cobro de $1,000 → el cobro (un recibo de
 * $1,000) se aplica $533 al pago 1 y $467 al pago 2 (le restan $66). Antes el
 * cobro se partía en varios cobros o cambiaba los montos de los demás pagos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobro_aplicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobro_id')->constrained('cobros')->cascadeOnDelete();
            $table->foreignId('venta_pago_id')->constrained('venta_pagos')->cascadeOnDelete();
            $table->decimal('monto', 12, 2);
            $table->timestamps();

            $table->index(['venta_pago_id', 'cobro_id']);
        });

        // Lo ya cobrado conserva su aplicación: cada cobro ligado a un pago
        // se aplica completo a ese pago, como estaba.
        $ahora = now();
        DB::table('cobros')->whereNotNull('venta_pago_id')->orderBy('id')->each(function ($c) use ($ahora) {
            DB::table('cobro_aplicaciones')->insert([
                'cobro_id' => $c->id,
                'venta_pago_id' => $c->venta_pago_id,
                'monto' => $c->monto,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobro_aplicaciones');
    }
};
