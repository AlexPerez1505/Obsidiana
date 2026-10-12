<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Services\Logistica\ControlDeFlota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un vehículo en el tablero de flota, con lo que calcula ControlDeFlota
 * (lo mismo que la pantalla de Flota): odómetro estimado (última lectura
 * + km de rutas posteriores), km y gasto del mes (combustible +
 * mantenimiento), rendimiento km/l entre cargas de tanque lleno y avisos
 * de mantenimiento.
 *
 * @mixin \App\Models\Vehicle
 */
class FlotaVehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $f = app(ControlDeFlota::class)->resumen($this->resource);

        return [
            'vehiculo' => new VehiculoResource($this->resource),
            'odometro_km' => $f['odometro'],
            'km_mes' => (float) $f['km_mes'],
            'gasto_mes' => (float) $f['gasto_mes'],
            'rendimiento_km_l' => $f['rendimiento'],
            'ultimo_mantenimiento' => $f['ultimo_mant'] ? new MantenimientoResource($f['ultimo_mant']) : null,
            'alertas' => $f['alertas'],
        ];
    }
}
