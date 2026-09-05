<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot de los números de serie que salieron en cada renglón, igual
     * que ya se hace con nombre/marca/modelo: el documento debe conservar
     * el dato tal como era el día que se vendió/facturó.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('venta_items', 'no_series')) {
            Schema::table('venta_items', function (Blueprint $table) {
                $table->text('no_series')->nullable()->after('cantidad');
            });
        }

        if (Schema::hasTable('factura_items') && ! Schema::hasColumn('factura_items', 'no_series')) {
            Schema::table('factura_items', function (Blueprint $table) {
                $table->text('no_series')->nullable()->after('cantidad');
            });
        }
    }

    public function down(): void
    {
        Schema::table('venta_items', function (Blueprint $table) {
            $table->dropColumn('no_series');
        });

        Schema::table('factura_items', function (Blueprint $table) {
            $table->dropColumn('no_series');
        });
    }
};
