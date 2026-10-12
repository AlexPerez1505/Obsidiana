<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuándo se avisó por última vez que el congreso terminó y sus piezas
 * siguen allá.
 *
 * Sin esta marca, el aviso se mandaría cada vez que corre la tarea y la
 * campana se volvería ruido que nadie lee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congresos_eventos', function (Blueprint $table) {
            if (! Schema::hasColumn('congresos_eventos', 'aviso_regreso_en')) {
                $table->timestamp('aviso_regreso_en')->nullable()->after('comments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('congresos_eventos', function (Blueprint $table) {
            $table->dropColumn('aviso_regreso_en');
        });
    }
};
