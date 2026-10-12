<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una refacción suelta. `subtype` es el nombre del subtipo de equipo al
 * que pertenece (texto, no liga al catálogo).
 *
 * @mixin \App\Models\Refaccion
 */
class RefaccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subtype' => $this->subtype,
            'name' => $this->name,
            'description' => $this->description,
            'stock' => $this->stock,
            'compatible_with' => $this->compatible_with,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
