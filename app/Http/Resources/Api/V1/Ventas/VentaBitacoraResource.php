<?php

namespace App\Http\Resources\Api\V1\Ventas;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una línea del historial de la venta: qué se le cambió, quién y cuándo.
 *
 * @mixin \App\Models\VentaBitacora
 */
class VentaBitacoraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'descripcion' => $this->descripcion,
            'datos' => $this->datos,
            'user_id' => $this->user_id,
            'user' => new UsuarioResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
