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
    /*
    | En producción ya existía fecha_caducidad (migración 2026_10_28 hecha en
    | el servidor): solo se crea la columna que falte.
    */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_movements', 'fecha_fabricacion')) {
                $table->date('fecha_fabricacion')->nullable()->after('movement_date');
            }

            if (! Schema::hasColumn('inventory_movements', 'fecha_caducidad')) {
                $table->date('fecha_caducidad')->nullable()->after('fecha_fabricacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('fecha_fabricacion');
        });
    }
};
