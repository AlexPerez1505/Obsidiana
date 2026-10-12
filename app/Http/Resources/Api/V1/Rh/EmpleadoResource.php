<?php

namespace App\Http\Resources\Api\V1\Rh;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;

/**
 * Un empleado visto desde RH: lo de cualquier usuario más su número de
 * checador y de nómina. Horario y huellas solo si se cargaron. Nunca trae
 * sueldo (eso es EmpleadoNominaResource, con su propio permiso).
 *
 * @mixin \App\Models\User
 */
class EmpleadoResource extends UsuarioResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'checador_id' => $this->checador_id,
            'payroll_number' => $this->payroll_number,
            'position' => $this->position,
            'fecha_ingreso' => $this->fecha_ingreso?->toDateString(),
            'horario' => HorarioResource::make($this->whenLoaded('horario')),
            'huellas' => HuellaResource::collection($this->whenLoaded('huellas')),
            'huellas_count' => $this->whenCounted('huellas'),
        ];
    }
}
