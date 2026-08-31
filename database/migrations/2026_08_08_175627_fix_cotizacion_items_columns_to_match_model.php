<?php

use Illuminate\Database\Migrations\Migration;

/*
|--------------------------------------------------------------------------
| MIGRACION NEUTRALIZADA
|--------------------------------------------------------------------------
| Hacia dropIfExists + create de `cotizacion_items` con el esquema de la
| generacion vieja (producto_id, precio_original, precio_final). Correrla
| destruia la tabla que crea 2026_08_02_000005 y dejaba una estructura que el
| modelo CotizacionItem no puede usar (espera equipo_id, tipo_item,
| precio_unitario, orden).
*/

return new class extends Migration
{
    public function up(): void
    {
        // Intencionalmente vacia. Ver 2026_08_02_000005_create_cotizacion_items_table.php
    }

    public function down(): void
    {
        // Intencionalmente vacia.
    }
};
