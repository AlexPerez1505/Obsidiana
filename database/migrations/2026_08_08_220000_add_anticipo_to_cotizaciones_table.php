<?php

use Illuminate\Database\Migrations\Migration;

/*
|--------------------------------------------------------------------------
| MIGRACION NEUTRALIZADA
|--------------------------------------------------------------------------
| Agregaba `anticipo` con after('costo_envio'), columna que solo existia en la
| version vieja de `cotizaciones`. En la tabla vigente el equivalente es
| `valor_a_cuenta` (2026_08_02_000004).
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
