<?php

namespace App\Http\Resources\Api\V1\Inventario;

use App\Models\FichaTecnica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Una ficha técnica: su título, el PDF y a qué producto o paquete va ligada
 * (cada producto tiene a lo más una).
 *
 * @mixin FichaTecnica
 */
class FichaTecnicaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipo_id' => $this->equipo_id,
            'producto_id' => $this->producto_id,
            'paquete_id' => $this->paquete_id,
            'titulo' => $this->titulo,
            'contenido' => $this->contenido,
            'activo' => (bool) $this->activo,
            'archivo_url' => $this->archivo ? Storage::disk('public')->url($this->archivo) : null,
            // Descarga con nombre legible (el del disco es aleatorio).
            'descarga_url' => $this->archivo ? route('api.v1.fichas-tecnicas.descargar', $this->id) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'producto' => new ProductoResource($this->whenLoaded('producto')),
            'paquete' => new PaqueteResource($this->whenLoaded('paquete')),
        ];
    }
}
