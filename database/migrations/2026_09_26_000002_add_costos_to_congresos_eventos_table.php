<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Costos del congreso: cuánto costó, cuánto se lleva pagado o apartado.
 *
 * El pendiente NO se guarda: es costo menos pagado, así que siempre sale
 * del cálculo y nunca puede quedar desfasado. Ambos montos son opcionales
 * para no romper la edición de los congresos que ya existen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congresos_eventos', function (Blueprint $table) {
            if (! Schema::hasColumn('congresos_eventos', 'costo')) {
                $table->decimal('costo', 12, 2)->nullable()->after('num_stand');
            }

            if (! Schema::hasColumn('congresos_eventos', 'pagado')) {
                $table->decimal('pagado', 12, 2)->default(0)->after('costo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('congresos_eventos', function (Blueprint $table) {
            $table->dropColumn(['costo', 'pagado']);
        });
    }
};
