<?php

use Illuminate\Database\Migrations\Migration;

/*
|--------------------------------------------------------------------------
| MIGRACION NEUTRALIZADA
|--------------------------------------------------------------------------
| Volvia a crear `cotizacion_items` con el esquema de la generacion vieja
| (producto_id, precio_original, precio_final, subtotal_linea) y agregaba
| `aplica_iva` despues de la columna `iva`, que no existe en la tabla
| cotizaciones vigente.
|
| La version vigente la crea 2026_08_02_000005_create_cotizacion_items_table.php
| con equipo_id, tipo_item, precio_unitario y orden, que es lo que declara
| el modelo CotizacionItem.
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
