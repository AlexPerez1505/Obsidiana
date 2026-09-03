<?php

use Illuminate\Database\Migrations\Migration;

/*
|--------------------------------------------------------------------------
| MIGRACION NEUTRALIZADA
|--------------------------------------------------------------------------
| Agregaba `user_id` despues de `cliente_id` en `cotizaciones`. Ninguna de las
| dos columnas existe en la tabla vigente (2026_08_02_000004), que guarda al
| asesor en `seller_id`.
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
