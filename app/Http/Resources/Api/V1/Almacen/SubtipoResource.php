<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Subtipo de equipo, dentro de un tipo.
 *
 * @mixin \App\Models\Subtype
 */
class SubtipoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipment_type_id' => $this->equipment_type_id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'equipment_type' => TipoEquipoResource::make($this->whenLoaded('equipmentType')),
            'brands' => MarcaResource::collection($this->whenLoaded('brands')),
        ];
    }
}
