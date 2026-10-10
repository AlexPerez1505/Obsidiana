<?php

namespace App\Http\Resources\Api\V1\Rh;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Incidencia del día (modalidad): por qué un empleado no checa, o checa
 * distinto, en un día o un rango (campo, home office, comisión,
 * vacaciones, incapacidad, permiso).
 *
 * @mixin \App\Models\AttendanceModality
 */
class IncidenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'empleado' => EmpleadoResource::make($this->whenLoaded('user')),
            'type' => $this->type,
            'tipo_label' => $this->tipoLabel(),
            'date_from' => $this->date_from?->toDateString(),
            'date_to' => $this->date_to?->toDateString(),
            'requires_fingerprint' => (bool) $this->requires_fingerprint,
            'time_start' => $this->time_start ? substr($this->time_start, 0, 5) : null,
            'time_end' => $this->time_end ? substr($this->time_end, 0, 5) : null,
            'destination' => $this->destination,
            'reason' => $this->reason,
            'created_by' => $this->created_by,
            'creador' => UsuarioResource::make($this->whenLoaded('creador')),
            'created_at' => $this->created_at,
        ];
    }
}
