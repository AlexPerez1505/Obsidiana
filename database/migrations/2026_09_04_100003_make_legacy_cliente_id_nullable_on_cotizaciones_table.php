<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cotizaciones') || ! Schema::hasColumn('cotizaciones', 'cliente_id')) {
            return;
        }

        if (Schema::hasColumn('cotizaciones', 'customer_id')) {
            DB::table('cotizaciones')
                ->whereNull('customer_id')
                ->whereNotNull('cliente_id')
                ->update(['customer_id' => DB::raw('cliente_id')]);

            DB::table('cotizaciones')
                ->whereNull('cliente_id')
                ->whereNotNull('customer_id')
                ->update(['cliente_id' => DB::raw('customer_id')]);
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE cotizaciones MODIFY cliente_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // No se revierte a NOT NULL para no romper cotizaciones heredadas sin cliente_id.
    }
};
