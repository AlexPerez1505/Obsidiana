<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un mismo número de serie no puede repetirse dos veces para el mismo
     * producto. MySQL trata cada NULL como distinto, así que las unidades
     * sin serial capturado (no_serie = null) no se ven afectadas.
     */
    public function up(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->unique(['producto_id', 'no_serie']);
        });
    }

    public function down(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->dropUnique(['producto_id', 'no_serie']);
        });
    }
};
