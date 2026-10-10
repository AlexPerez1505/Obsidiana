<?php

namespace App\Http\Resources\Api\V1\Rh;

use Illuminate\Http\Request;

/**
 * Un empleado en la configuración de nómina: con su sueldo quincenal y
 * sus conceptos. Solo lo devuelven rutas con el permiso asistencia.nomina.
 *
 * @mixin \App\Models\User
 */
class EmpleadoNominaResource extends EmpleadoResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'sueldo_quincenal' => $this->sueldo_quincenal !== null ? (float) $this->sueldo_quincenal : null,
            // Solo quien tiene horario activo entra al cálculo de la quincena
            'toma_asistencia' => $this->whenLoaded('horario', fn () => (bool) $this->horario?->active),
            'conceptos' => ConceptoResource::collection($this->whenLoaded('conceptos')),
        ];
    }
}
