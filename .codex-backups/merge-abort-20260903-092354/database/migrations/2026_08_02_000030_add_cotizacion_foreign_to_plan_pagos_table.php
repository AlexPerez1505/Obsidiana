<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Llave foranea plan_pagos.cotizacion_id -> cotizaciones.id
|--------------------------------------------------------------------------
| `plan_pagos` se crea en 2026_01_01_000007, antes que la tabla `cotizaciones`
| vigente (2026_08_02_000004). Por eso la columna se crea alli sin llave
| foranea y la restriccion se agrega aqui, cuando ambas tablas ya existen.
*/

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_pagos') || ! Schema::hasTable('cotizaciones')) {
            return;
        }

        $exists = Schema::getConnection()->select(
            "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plan_pagos' AND CONSTRAINT_NAME = 'plan_pagos_cotizacion_id_foreign'"
        );

        if (! empty($exists)) {
            return;
        }

        Schema::table('plan_pagos', function (Blueprint $table) {
            $table->foreign('cotizacion_id')
                ->references('id')
                ->on('cotizaciones')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('plan_pagos')) {
            return;
        }

        Schema::table('plan_pagos', function (Blueprint $table) {
            $table->dropForeign(['cotizacion_id']);
        });
    }
};
