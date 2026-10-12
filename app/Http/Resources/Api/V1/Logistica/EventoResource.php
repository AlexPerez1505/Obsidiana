<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un renglón de la bitácora de la ruta (salida, arribos, entregas,
 * desviaciones, incidencias…), con quién lo hizo y dónde.
 *
 * @mixin \App\Models\RutaEvento
 */
class EventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ruta_id' => $this->ruta_id,
            'ruta_parada_id' => $this->ruta_parada_id,
            'tipo' => $this->tipo,
            'detalle' => $this->detalle,
            'user_id' => $this->user_id,
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'ocurrio_en' => $this->ocurrio_en,
        ];
    }
}
