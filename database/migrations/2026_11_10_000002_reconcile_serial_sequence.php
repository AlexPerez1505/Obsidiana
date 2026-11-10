<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reconciles the global counter after older versions counted factory
     * numbers such as 2105-4774 as generated series.
     */
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('serial_sequences')
            || ! DB::getSchemaBuilder()->hasTable('producto_seriales')) {
            return;
        }

        $ultimo = DB::table('producto_seriales')
            ->whereNotNull('no_serie')
            ->where('no_serie', '!=', '')
            ->pluck('no_serie')
            ->map(function (string $serie): ?int {
                return preg_match('/^(?=.*[A-Za-z]).*-(\d{4})$/', trim($serie), $coincidencia)
                    ? (int) $coincidencia[1]
                    : null;
            })
            ->filter()
            ->max() ?: 0;

        DB::table('serial_sequences')
            ->where('prefijo', '__global__')
            ->update([
                'siguiente' => $ultimo + 1,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // This data reconciliation is intentionally not reverted.
    }
};
