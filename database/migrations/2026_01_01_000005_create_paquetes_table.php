<?php

use Illuminate\Database\Migrations\Migration;

/*
|--------------------------------------------------------------------------
| MIGRACION NEUTRALIZADA
|--------------------------------------------------------------------------
| Creaba la tabla `paquetes` de la generacion vieja del sistema (con
| producto_id). La version vigente la crea 2026_08_02_000002_create_paquetes_table.php
| con las columnas que espera el modelo Paquete (precio, imagen, activo)
| mas la pivote paquete_equipo.
|
| Se deja vacia en lugar de borrarse para no romper el historial de
| migraciones ya aplicado en otros entornos.
*/

return new class extends Migration
{
    public function up(): void
    {
        // Intencionalmente vacia. Ver 2026_08_02_000002_create_paquetes_table.php
    }

    public function down(): void
    {
        // Intencionalmente vacia.
    }
};
