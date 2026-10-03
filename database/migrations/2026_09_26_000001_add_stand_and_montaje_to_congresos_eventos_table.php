<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Número de stand y fecha de montaje del congreso.
 *
 * El montaje casi nunca es el mismo día en que arranca el congreso: se llega
 * antes a armar el stand. Antes solo se guardaba la HORA de montaje, así que
 * no había forma de saber en qué día era. Ahora la fecha de montaje va por
 * separado de la fecha de inicio. Ambos campos son opcionales para no romper
 * la edición de los congresos que ya existen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congresos_eventos', function (Blueprint $table) {
            if (! Schema::hasColumn('congresos_eventos', 'num_stand')) {
                $table->string('num_stand', 50)->nullable()->after('direccion');
            }

            if (! Schema::hasColumn('congresos_eventos', 'fecha_montaje')) {
                $table->date('fecha_montaje')->nullable()->after('fecha_finalizacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('congresos_eventos', function (Blueprint $table) {
            $table->dropColumn(['num_stand', 'fecha_montaje']);
        });
    }
};
