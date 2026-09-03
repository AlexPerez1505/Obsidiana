<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garantía del equipo, en meses.
 *
 * Va también en cotizaciones para que se acuerde desde la propuesta y se
 * arrastre sola al convertirla en venta; si solo viviera en la venta habría
 * que volver a capturarla.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            if (! Schema::hasTable($tabla) || Schema::hasColumn($tabla, 'garantia_meses')) {
                continue;
            }

            $after = match (true) {
                Schema::hasColumn($tabla, 'num_meses') => 'num_meses',
                Schema::hasColumn($tabla, 'total') => 'total',
                default => 'id',
            };

            Schema::table($tabla, function (Blueprint $table) use ($after) {
                $table->unsignedSmallInteger('garantia_meses')->default(6)->after($after);
            });
        }
    }

    public function down(): void
    {
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            if (! Schema::hasColumn($tabla, 'garantia_meses')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('garantia_meses');
            });
        }
    }
};
