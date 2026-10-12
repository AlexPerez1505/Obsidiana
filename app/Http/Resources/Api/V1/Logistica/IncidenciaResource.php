<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Http\Resources\Api\V1\Logistica\Concerns\UrlCompleta;
use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un problema que reportó el chofer en el camino. Sigue abierta mientras
 * `resuelta_en` sea nulo.
 *
 * @mixin \App\Models\RutaIncidencia
 */
class IncidenciaResource extends JsonResource
{
    use UrlCompleta;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ruta_id' => $this->ruta_id,
            'ruta_parada_id' => $this->ruta_parada_id,
            'user_id' => $this->user_id,
            'tipo' => $this->tipo,
            'tipo_label' => $this->tipoLabel(),
            'descripcion' => $this->descripcion,
            'foto_url' => self::urlCompleta($this->fotoUrl()),
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'resuelta_en' => $this->resuelta_en,
            'resuelta_por' => $this->resuelta_por,
            'resolucion' => $this->resolucion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
            'ruta' => new RutaResource($this->whenLoaded('ruta')),
            'parada' => new ParadaResource($this->whenLoaded('parada')),
        ];
    }
}
