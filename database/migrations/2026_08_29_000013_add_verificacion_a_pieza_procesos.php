<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que hace falta para poder cerrar un proceso.
 *
 * Un paso no se cierra con un botón: se cierra demostrando que el equipo
 * quedó funcionando. Por eso cada paso guarda su propia verificación
 * (checklist de salida) y las fotos que la respaldan.
 *
 * Sin eso, la pieza no pasa a stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pieza_procesos', function (Blueprint $table) {
            // Respuestas del checklist de salida de ESTE proceso.
            $table->json('checklist_salida')->nullable()->after('notas');

            // Fotos de que quedó funcionando.
            $table->json('evidencias')->nullable()->after('checklist_salida');

            // Qué se le hizo, en palabras de quien lo trabajó.
            $table->text('trabajo_realizado')->nullable()->after('evidencias');

            // Quién autorizó cerrarlo, que puede no ser quien lo trabajó.
            $table->foreignId('cerrado_por')->nullable()->after('responsable_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pieza_procesos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cerrado_por');
            $table->dropColumn(['checklist_salida', 'evidencias', 'trabajo_realizado']);
        });
    }
};
