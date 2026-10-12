<?php

namespace App\Http\Resources\Api\V1\Logistica;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un punto GPS del vehículo. `segundos` es cuánto hace que se tomó, para
 * que la app avise cuando el teléfono del chofer dejó de reportar.
 *
 * @mixin \App\Models\RutaUbicacion
 */
class UbicacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'lat' => (float) $this->lat,
            'lng' => (float) $this->lng,
            'velocidad_kmh' => $this->velocidad_kmh !== null ? (float) $this->velocidad_kmh : null,
            'precision_m' => $this->precision_m !== null ? (float) $this->precision_m : null,
            'rumbo' => $this->rumbo !== null ? (float) $this->rumbo : null,
            'registrada_en' => $this->registrada_en,
            'segundos' => (int) $this->registrada_en->diffInSeconds(now()),
        ];
    }
}
