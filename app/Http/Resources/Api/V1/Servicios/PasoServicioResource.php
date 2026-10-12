<?php

namespace App\Http\Resources\Api\V1\Servicios;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un paso del recorrido de una orden (p. ej. "Salida a mantenimiento
 * foráneo"). Cada tipo de servicio tiene los suyos, en orden.
 *
 * @mixin \App\Models\ServiceStep
 */
class PasoServicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_type' => $this->service_type,
            'name' => $this->name,
            'code' => $this->code,
            'order' => (int) $this->order,
            'requires_qr' => (bool) $this->requires_qr,
            'requires_signature' => (bool) $this->requires_signature,
            'is_final' => (bool) $this->is_final,
        ];
    }
}
