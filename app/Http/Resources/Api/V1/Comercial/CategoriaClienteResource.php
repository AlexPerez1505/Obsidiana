<?php

namespace App\Http\Resources\Api\V1\Comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo de equipo del catálogo de equipo con el que se clasifica al cliente.
 *
 * @mixin \App\Models\EquipmentType
 */
class CategoriaClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'descripcion' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
