<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tienda en línea deja de ser automática: antes salía público todo
 * producto con stock. Con esta bandera cada producto decide si se muestra;
 * por omisión no sale en la web.
 *
 * En producción la columna ya existía (se creó desde el servidor el
 * 2026-10-26), con lo que ya se había marcado: solo se crea si falta.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('productos', 'en_tienda')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('en_tienda')->default(false)->after('es_serializado');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'en_tienda')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('en_tienda');
            });
        }
    }
};
