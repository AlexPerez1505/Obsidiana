<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca si este producto exige serie + foto por unidad al capturar una
     * entrada, o si solo maneja cantidad (como hasta ahora). Se define una
     * vez, al dar de alta el modelo en catálogo.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('es_serializado')->default(false)->after('no_serie');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('es_serializado');
        });
    }
};
