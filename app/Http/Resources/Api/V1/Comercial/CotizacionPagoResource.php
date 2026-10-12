<?php

namespace App\Http\Resources\Api\V1\Comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una parcialidad del plan de pagos propuesto. Los `bloqueado` conservan
 * el monto que se les puso; el resto del total del contrato se reparte
 * entre los demás.
 *
 * @mixin \App\Models\CotizacionPago
 */
class CotizacionPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cotizacion_id' => $this->cotizacion_id,
            'nombre' => $this->nombre,
            'fecha' => $this->fecha?->toDateString(),
            'porcentaje' => (float) $this->porcentaje,
            'monto' => (float) $this->monto,
            'bloqueado' => (bool) $this->bloqueado,
            'orden' => (int) $this->orden,
        ];
    }
}
