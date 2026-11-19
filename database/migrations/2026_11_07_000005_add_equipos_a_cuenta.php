<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qué equipo entrega el cliente a cuenta (tipo, subtipo, marca, modelo y
 * serie), además del valor que ya se guardaba en valor_a_cuenta.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                if (! Schema::hasColumn($tabla, 'equipos_a_cuenta')) {
                    $table->json('equipos_a_cuenta')->nullable()->after('valor_a_cuenta');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                if (Schema::hasColumn($tabla, 'equipos_a_cuenta')) {
                    $table->dropColumn('equipos_a_cuenta');
                }
            });
        }
    }
};
