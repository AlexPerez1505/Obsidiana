<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IVA que se le agregó a una mensualidad de una venta que salió sin IVA.
 * Se guarda aparte para poder enseñarlo y, si hace falta, quitarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_pagos', function (Blueprint $table) {
            if (! Schema::hasColumn('venta_pagos', 'iva_monto')) {
                $table->decimal('iva_monto', 12, 2)->default(0)->after('monto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('venta_pagos', function (Blueprint $table) {
            if (Schema::hasColumn('venta_pagos', 'iva_monto')) {
                $table->dropColumn('iva_monto');
            }
        });
    }
};
