<?php

namespace App\Http\Resources\Api\V1\Rh;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\EmployeeBiometric;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Solicitud de RH para registrar una huella en un lector. El agente la
 * recoge y avanza `step` (lecturas hechas, 0-3) hasta `done` o `failed`.
 *
 * @mixin \App\Models\BiometricEnrollment
 */
class InscripcionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'empleado' => UsuarioResource::make($this->whenLoaded('user')),
            'device_id' => $this->device_id,
            'dispositivo' => $this->whenLoaded('device', fn () => $this->device ? [
                'id' => $this->device->id,
                'name' => $this->device->name,
                'code' => $this->device->code,
            ] : null),
            'finger_index' => $this->finger_index,
            'dedo' => EmployeeBiometric::DEDOS[$this->finger_index] ?? 'Dedo '.$this->finger_index,
            // pending | capturing | done | failed | cancelled
            'status' => $this->status,
            'step' => $this->step,
            'message' => $this->message,
            'vigente' => $this->expires_at ? $this->vigente() : false,
            'requested_by' => $this->requested_by,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
