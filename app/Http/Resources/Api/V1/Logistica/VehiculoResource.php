<?php

namespace App\Http\Resources\Api\V1\Logistica;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un vehículo tal como lo necesita logística (placas y modelo). Los
 * documentos y fotos del vehículo se consultan en Administración.
 *
 * @mixin \App\Models\Vehicle
 */
class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plate_number' => $this->plate_number,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year !== null ? (int) $this->year : null,
            'color' => $this->color,
            'status' => $this->status,
        ];
    }
}
