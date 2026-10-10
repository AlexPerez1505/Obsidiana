<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Services\Administracion\ReportesDePersonal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un movimiento de personal: asistencia, falta, vacaciones, permiso o
 * incidencia.
 *
 * @mixin \App\Models\EmployeeReport
 */
class ReporteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'empleado' => new UsuarioResource($this->whenLoaded('employee')),
            'employee_name' => $this->employee_name,
            'employee_initials' => $this->employee_initials,
            'type' => $this->type,
            'label' => $this->label ?: (ReportesDePersonal::TIPOS[$this->type]['label'] ?? null),
            'status' => $this->status,
            'status_label' => ReportesDePersonal::ESTADOS[$this->status] ?? $this->status,
            'area' => $this->area,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'check_in' => $this->check_in ? substr((string) $this->check_in, 0, 5) : null,
            'check_out' => $this->check_out ? substr((string) $this->check_out, 0, 5) : null,
            'late_minutes' => $this->late_minutes,
            'detail' => $this->detail,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
