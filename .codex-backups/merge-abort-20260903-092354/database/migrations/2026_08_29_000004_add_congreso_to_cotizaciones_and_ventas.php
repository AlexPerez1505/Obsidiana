<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El "lugar de la propuesta" pasa a ser el congreso donde se levantó.
 *
 * La columna de texto lugar_propuesta NO se elimina: el PDF de documentos
 * ya emitidos la lee, y ademas sirve como fotografia del nombre que tenia
 * el congreso ese dia. El modelo la mantiene al dia desde congreso_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            if (! Schema::hasTable($tabla) || Schema::hasColumn($tabla, 'congreso_id')) {
                continue;
            }

            $after = match (true) {
                Schema::hasColumn($tabla, 'customer_id') => 'customer_id',
                Schema::hasColumn($tabla, 'cliente_id') => 'cliente_id',
                default => 'id',
            };

            Schema::table($tabla, function (Blueprint $table) use ($after) {
                $table->foreignId('congreso_id')->nullable()->after($after)
                    ->constrained('congresos_eventos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            if (! Schema::hasColumn($tabla, 'congreso_id')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('congreso_id');
            });
        }
    }
};
