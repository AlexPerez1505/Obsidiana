<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un mismo modelo de catálogo solo puede tener una fila en productos
     * (es la fila donde se acumulan las unidades/seriales de ese modelo).
     * Los productos sin modelo de catálogo (equipment_model_id = null)
     * no se ven afectados: MySQL trata cada NULL como distinto.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unique('equipment_model_id');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['equipment_model_id']);
        });
    }
};
