<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Http\Resources\Api\V1\Logistica\Concerns\UrlCompleta;
use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una carga de combustible. `ruta_id` viene cuando la registró el chofer
 * desde una ruta.
 *
 * @mixin \App\Models\VehiculoCombustible
 */
class CombustibleResource extends JsonResource
{
    use UrlCompleta;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'ruta_id' => $this->ruta_id,
            'user_id' => $this->user_id,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'litros' => (float) $this->litros,
            'monto' => (float) $this->monto,
            'odometro_km' => $this->odometro_km !== null ? (int) $this->odometro_km : null,
            'tanque_lleno' => (bool) $this->tanque_lleno,
            'estacion' => $this->estacion,
            'ticket_url' => self::urlCompleta($this->ticketUrl()),
            'created_at' => $this->created_at,
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
        ];
    }
}
