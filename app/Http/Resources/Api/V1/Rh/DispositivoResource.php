<?php

namespace App\Http\Resources\Api\V1\Rh;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un lector de huella (la PC con el agente). Sin secretos: la llave del
 * agente y el token de la pantalla solo salen, una vez, al crear el lector
 * o al regenerar su llave.
 *
 * @mixin \App\Models\BiometricDevice
 */
class DispositivoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'serial_number' => $this->serial_number,
            'location' => $this->location,
            'status' => $this->status,
            // Reportó en los últimos 2 minutos
            'en_linea' => $this->enLinea(),
            'last_seen_at' => $this->last_seen_at,
            'last_ip' => $this->last_ip,
            'agent_version' => $this->agent_version,
            'checadas_hoy' => $this->whenHas('checadas_hoy', fn () => (int) $this->checadas_hoy),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
