<?php

namespace App\Http\Resources\Api\V1\Inventario;

use App\Models\Paquete;
use App\Support\PrecioVisible;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Un paquete armado con productos, y cuántas piezas de cada uno lleva
 * (`productos[].cantidad`). `precio` solo aparece con `precios.ver`.
 *
 * @mixin Paquete
 */
class PaqueteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->when(
                PrecioVisible::para($request->user()),
                fn () => $this->precio === null ? null : (float) $this->precio,
            ),
            'imagen_url' => $this->imagen ? Storage::disk('public')->url($this->imagen) : null,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'productos' => ProductoResource::collection($this->whenLoaded('productos')),
        ];
    }
}
