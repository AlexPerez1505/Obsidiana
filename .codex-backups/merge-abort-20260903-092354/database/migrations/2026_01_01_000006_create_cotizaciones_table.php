<?php

use Illuminate\Database\Migrations\Migration;

/*
|--------------------------------------------------------------------------
| MIGRACION NEUTRALIZADA
|--------------------------------------------------------------------------
| Creaba la tabla `cotizaciones` de la generacion vieja (cliente_id,
| producto_id, iva, costo_envio, plan_pago_id...). La version vigente la crea
| 2026_08_02_000004_create_cotizaciones_table.php con folio, customer_id,
| seller_id, aplica_iva e iva_monto, que es lo que usan el modelo Cotizacion
| y CotizacionController.
|
| Se deja vacia en lugar de borrarse para no romper el historial de
| migraciones ya aplicado en otros entornos.
*/

return new class extends Migration
{
    public function up(): void
    {
        // Intencionalmente vacia. Ver 2026_08_02_000004_create_cotizaciones_table.php
    }

    public function down(): void
    {
        // Intencionalmente vacia.
    }
};
