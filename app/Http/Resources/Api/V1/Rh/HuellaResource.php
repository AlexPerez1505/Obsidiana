<?php

namespace App\Http\Resources\Api\V1\Rh;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una huella registrada: qué dedo, cuándo y en qué lector. La plantilla
 * biométrica NUNCA sale por la API (solo la usa el agente del lector).
 *
 * @mixin \App\Models\EmployeeBiometric
 */
class HuellaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'finger_index' => $this->finger_index,
            'dedo' => $this->dedoLabel(),
            'template_format' => $this->template_format,
            'device_id' => $this->device_id,
            'status' => $this->status,
            'registered_at' => $this->registered_at,
            'registered_by' => $this->registered_by,
            'created_at' => $this->created_at,
        ];
    }
}
