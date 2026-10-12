<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo de equipo (primer nivel del catálogo). `requiere_emplayado` dice si
 * lo vendido de este tipo se emplaya antes de salir.
 *
 * @mixin \App\Models\EquipmentType
 */
class TipoEquipoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'requiere_emplayado' => (bool) $this->requiere_emplayado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'subtypes_count' => $this->whenCounted('subtypes'),
            'subtypes' => SubtipoResource::collection($this->whenLoaded('subtypes')),
        ];
    }
}
