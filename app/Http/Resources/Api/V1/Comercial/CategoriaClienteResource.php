<?php

namespace App\Http\Resources\Api\V1\Comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una categoría para clasificar clientes (hospital, clínica, médico…).
 *
 * @mixin \App\Models\Category
 */
class CategoriaClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
