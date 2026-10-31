<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fechas opcionales del lote que entra: fabricación (nuevo y usado) y
 * caducidad (solo nuevo). Van en la entrada porque todas las piezas de una
 * misma entrada llegan del mismo lote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->date('fecha_fabricacion')->nullable()->after('movement_date');
            $table->date('fecha_caducidad')->nullable()->after('fecha_fabricacion');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['fecha_fabricacion', 'fecha_caducidad']);
        });
    }
};
