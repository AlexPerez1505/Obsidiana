<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Modelo de equipo: de una marca, dentro de un subtipo.
 *
 * @mixin \App\Models\EquipmentModel
 */
class ModeloResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'subtype_id' => $this->subtype_id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'brand' => MarcaResource::make($this->whenLoaded('brand')),
            'subtype' => SubtipoResource::make($this->whenLoaded('subtype')),
        ];
    }
}
