<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda como cada usuario acomoda su tablero: que tarjetas ve, en que
 * orden y de que tamaño. Nulo = todavia no lo personaliza, se le muestra
 * el arreglo por omision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('dashboard_widgets')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dashboard_widgets');
        });
    }
};
