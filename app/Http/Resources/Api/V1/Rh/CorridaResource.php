<?php

namespace App\Http\Resources\Api\V1\Rh;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una quincena cerrada (corrida de nómina): sus totales quedan congelados
 * aunque después cambien la asistencia o las reglas.
 *
 * @mixin \App\Models\PayrollRun
 */
class CorridaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            // cerrada | pagada
            'status' => $this->status,
            'total_sueldos' => (float) $this->total_sueldos,
            'total_bonos' => (float) $this->total_bonos,
            'total_descuentos' => (float) $this->total_descuentos,
            'total_neto' => (float) $this->total_neto,
            'employees' => (int) $this->employees,
            'closed_at' => $this->closed_at,
            'closed_by' => $this->closed_by,
            'cerro' => UsuarioResource::make($this->whenLoaded('cerro')),
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
