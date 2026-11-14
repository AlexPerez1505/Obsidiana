<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dirección del cliente por partes (estado, municipio, colonia, CP, calle).
 * `direccion` se queda como el texto completo que se arma con ellas: lo
 * siguen leyendo logística, los PDF y la API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('estado', 60)->nullable()->after('direccion');
            $table->string('municipio', 120)->nullable()->after('estado');
            $table->string('colonia', 150)->nullable()->after('municipio');
            $table->string('codigo_postal', 5)->nullable()->after('colonia');
            $table->string('calle', 200)->nullable()->after('codigo_postal');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['estado', 'municipio', 'colonia', 'codigo_postal', 'calle']);
        });
    }
};
