<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            // Que una unidad esté marcada "en congreso" es solo informativo:
            // no la bloquea para venderse ni cambia su estado. Es nada más
            // para poder ver, al consultar esa pieza puntual, a qué congreso
            // se la llevaron.
            $table->foreignId('congress_id')->nullable()->after('inventory_movement_id')
                ->constrained('congresos_eventos')->nullOnDelete();
            $table->timestamp('enviado_a_congreso_en')->nullable()->after('congress_id');
        });
    }

    public function down(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('congress_id');
            $table->dropColumn('enviado_a_congreso_en');
        });
    }
};
