<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Marca. Es global, pero se declara en qué subtipos se ofrece.
 *
 * @mixin \App\Models\Brand
 */
class MarcaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'subtypes' => SubtipoResource::collection($this->whenLoaded('subtypes')),
        ];
    }
}
