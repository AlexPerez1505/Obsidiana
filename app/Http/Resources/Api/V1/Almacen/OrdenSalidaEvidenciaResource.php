<?php

namespace App\Http\Resources\Api\V1\Almacen;

use App\Http\Resources\Api\V1\Logistica\Concerns\UrlCompleta;
use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una foto o un video de evidencia de la orden de salida.
 *
 * @mixin \App\Models\OrdenSalidaEvidencia
 */
class OrdenSalidaEvidenciaResource extends JsonResource
{
    use UrlCompleta;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orden_salida_id' => $this->orden_salida_id,
            // foto | video
            'tipo' => $this->tipo,
            'url' => self::urlCompleta($this->url()),
            'nombre_original' => $this->nombre_original,
            // Bytes.
            'tamano' => $this->tamano,
            'subido_por' => $this->subido_por,
            'subido_por_usuario' => new UsuarioResource($this->whenLoaded('subidoPor')),
            // Si quien consulta la puede quitar (la subió él o prepara salidas).
            'puede_borrar' => $request->user() ? $this->puedeBorrarla($request->user()) : false,
            'created_at' => $this->created_at,
        ];
    }
}
