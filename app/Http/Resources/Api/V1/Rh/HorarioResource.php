<?php

namespace App\Http\Resources\Api\V1\Rh;

use App\Models\EmployeeSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Horario de un empleado. Además de las columnas trae `days`, el horario
 * de cada día de la semana (1 = lunes … 7 = domingo) con la misma forma
 * que se manda al guardarlo, para editarlo y devolverlo tal cual.
 *
 * @mixin \App\Models\EmployeeSchedule
 */
class HorarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dias = [];
        foreach (EmployeeSchedule::DIAS as $n => $nombre) {
            $h = $this->horasDelDia($n);
            $dias[(string) $n] = ['nombre' => $nombre, 'on' => in_array($n, array_map('intval', $this->weekdays ?? []), true), 'in' => $h['in'], 'out' => $h['out']];
        }

        // Como objeto: un arreglo con llaves numéricas el resource lo volvería
        // lista y se perdería qué día es cada uno
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'weekdays' => array_values(array_map('intval', $this->weekdays ?? [])),
            'day_hours' => $this->day_hours ? (object) $this->day_hours : null,
            'time_in' => $this->entradaHHMM(),
            'time_out' => $this->salidaHHMM(),
            'tolerance_minutes' => $this->tolerance_minutes,
            'active' => $this->active,
            'resumen' => $this->resumen(),
            'days' => (object) $dias,
            'updated_at' => $this->updated_at,
        ];
    }
}
