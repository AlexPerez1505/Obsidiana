<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quién prepara cada equipo (partida) de la orden de salida, no solo la
 * orden completa: dos personas pueden preparar equipos distintos de la
 * misma venta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_salida_items', function (Blueprint $table) {
            $table->foreignId('asignado_a')->nullable()->after('observaciones')->constrained('users')->nullOnDelete();
            $table->foreignId('asignado_por')->nullable()->after('asignado_a')->constrained('users')->nullOnDelete();
            $table->timestamp('asignado_en')->nullable()->after('asignado_por');
        });

        // Lo que ya estaba asignado por orden pasa a cada uno de sus equipos.
        DB::statement('
            UPDATE orden_salida_items i
            JOIN ordenes_salida o ON o.id = i.orden_salida_id
            SET i.asignado_a = o.asignada_a, i.asignado_por = o.asignada_por, i.asignado_en = o.asignada_en
            WHERE o.asignada_a IS NOT NULL AND i.asignado_a IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('orden_salida_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asignado_por');
            $table->dropConstrainedForeignId('asignado_a');
            $table->dropColumn('asignado_en');
        });
    }
};
