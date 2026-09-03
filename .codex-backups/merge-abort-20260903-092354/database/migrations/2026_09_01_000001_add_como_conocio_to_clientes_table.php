<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Si el cliente no se conoció en un congreso, se escribe a mano cómo
     * llegó (recomendación, redes sociales, etc.). Con congreso_id ya
     * seleccionado, este campo se deja vacío: el congreso ya responde
     * "cómo lo conocimos".
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('como_conocio')->nullable()->after('congreso_id');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('como_conocio');
        });
    }
};
