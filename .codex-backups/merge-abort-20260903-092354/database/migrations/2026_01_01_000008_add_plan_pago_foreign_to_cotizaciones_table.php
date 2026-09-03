<?php

use Illuminate\Database\Migrations\Migration;

/*
|--------------------------------------------------------------------------
| MIGRACION NEUTRALIZADA
|--------------------------------------------------------------------------
| Agregaba la llave foranea `plan_pago_id` a `cotizaciones`. Esa columna solo
| existia en la version vieja de la tabla, que ya no se crea.
| La tabla vigente (2026_08_02_000004) no tiene plan_pago_id: el plan se
| guarda en plan_nombre / num_meses y los pagos en cotizacion_pagos.
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
