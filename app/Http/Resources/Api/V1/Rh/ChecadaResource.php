<?php

namespace App\Http\Resources\Api\V1\Rh;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un toque del lector (o una checada manual). No se edita: una equivocada
 * se anula y queda con quién, cuándo y por qué. `punched_at` va en UTC;
 * `hora_local` es la hora en la zona de las reglas de asistencia.
 *
 * @mixin \App\Models\AttendancePunch
 */
class ChecadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'empleado' => EmpleadoResource::make($this->whenLoaded('user')),
            'device_id' => $this->device_id,
            'dispositivo' => $this->whenLoaded('device', fn () => $this->device ? [
                'id' => $this->device->id,
                'name' => $this->device->name,
                'code' => $this->device->code,
                'location' => $this->device->location,
            ] : null),
            'punched_at' => $this->punched_at,
            'hora_local' => $this->horaLocal()->format('H:i:s'),
            'work_date' => $this->work_date?->toDateString(),
            'method' => $this->method,
            'confidence' => $this->confidence,
            'kind' => $this->kind,
            'kind_label' => $this->kindLabel(),
            'seq' => $this->seq,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'anulada' => $this->voided_at !== null,
            'voided_at' => $this->voided_at,
            'voided_by' => $this->voided_by,
            'anulada_por' => UsuarioResource::make($this->whenLoaded('anuladaPor')),
            'void_reason' => $this->void_reason,
            'created_at' => $this->created_at,
        ];
    }
}
