<?php

use Illuminate\Database\Migrations\Migration;

/*
|--------------------------------------------------------------------------
| MIGRACION NEUTRALIZADA
|--------------------------------------------------------------------------
| Reemplazaba `estado` usando after('plan_pago_id'), columna que solo existia
| en la version vieja de `cotizaciones`.
| La tabla vigente (2026_08_02_000004) ya define estado como enum
| ('borrador','enviada','aceptada','rechazada','convertida').
*/

return new class extends Migration
{
    public function up(): void
    {
        // Intencionalmente vacia.
    }

    public function down(): void
    {
        // Intencionalmente vacia.
    }
};
