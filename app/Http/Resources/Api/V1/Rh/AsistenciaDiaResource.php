<?php

namespace App\Http\Resources\Api\V1\Rh;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El resumen de un día de un empleado, calculado de sus checadas:
 * entrada, salida, retardo, minutos trabajados y estado
 * (puntual | retardo | falta | justificada). Las horas son locales.
 *
 * @mixin \App\Models\EmployeeAttendance
 */
class AsistenciaDiaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'attendance_date' => $this->attendance_date?->toDateString(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'late_minutes' => (int) $this->late_minutes,
            'worked_minutes' => (int) $this->worked_minutes,
            'punches_count' => (int) $this->punches_count,
            'modality' => $this->modality,
            'incomplete' => (bool) $this->incomplete,
            'notes' => $this->notes,
            'validated_by' => $this->validated_by,
            'validated_at' => $this->validated_at,
        ];
    }
}
