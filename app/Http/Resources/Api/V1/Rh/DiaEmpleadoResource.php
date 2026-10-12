<?php

namespace App\Http\Resources\Api\V1\Rh;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un día del mes de un empleado (ReporteDeAsistencia::mesDeEmpleado):
 * si se le esperaba, si fue festivo, qué lo justifica, su resumen y sus
 * checadas crudas (incluidas las anuladas).
 *
 * @property array $resource
 */
class DiaEmpleadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $d = $this->resource;

        return [
            'fecha' => $d['fecha']->toDateString(),
            'esperado' => $d['esperado'],
            'festivo' => $d['festivo'],
            // {type, label, requires_fingerprint}: incidencia, vacaciones, permiso o evento de empresa
            'modalidad' => $d['modalidad'],
            'asistencia' => $d['fila'] ? new AsistenciaDiaResource($d['fila']) : null,
            'checadas' => ChecadaResource::collection($d['checadas']),
        ];
    }
}
