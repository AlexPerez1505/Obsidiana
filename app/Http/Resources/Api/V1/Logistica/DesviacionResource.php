<?php

namespace App\Http\Resources\Api\V1\Logistica;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un tramo en que el vehículo se salió del trazo planeado. Sigue abierta
 * (`fin_en` nulo) mientras no regrese.
 *
 * @mixin \App\Models\RutaDesviacion
 */
class DesviacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lat' => (float) $this->lat,
            'lng' => (float) $this->lng,
            'max_distancia_m' => (int) $this->max_distancia_m,
            'inicio_en' => $this->inicio_en,
            'fin_en' => $this->fin_en,
        ];
    }
}
