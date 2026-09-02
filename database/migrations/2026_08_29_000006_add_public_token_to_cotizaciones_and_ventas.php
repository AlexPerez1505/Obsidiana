<?php

use App\Models\Cotizacion;
use App\Models\Venta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Enlace publico de consulta.
 *
 * El cliente entra por un token largo, nunca por el id: si la direccion
 * fuera /cotizacion/5 cualquiera podria ir probando numeros y leer las
 * cotizaciones de los demas.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            if (Schema::hasColumn($tabla, 'public_token')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->uuid('public_token')->nullable()->unique()->after('folio');
            });
        }

        // Los documentos que ya existen también necesitan el suyo.
        Cotizacion::whereNull('public_token')->each(
            fn (Cotizacion $c) => $c->forceFill(['public_token' => (string) Str::uuid()])->saveQuietly()
        );

        Venta::whereNull('public_token')->each(
            fn (Venta $v) => $v->forceFill(['public_token' => (string) Str::uuid()])->saveQuietly()
        );
    }

    public function down(): void
    {
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            if (! Schema::hasColumn($tabla, 'public_token')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->dropUnique([$table->getTable() . '_public_token_unique']);
                $table->dropColumn('public_token');
            });
        }
    }
};
