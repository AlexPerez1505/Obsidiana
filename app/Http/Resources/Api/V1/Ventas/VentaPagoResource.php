<?php

namespace App\Http\Resources\Api\V1\Ventas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una parcialidad del plan de pagos. Con los cobros cargados trae también
 * cuánto se le ha aplicado, cuánto le falta y si ya venció.
 *
 * @mixin \App\Models\VentaPago
 */
class VentaPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $conCobros = $this->relationLoaded('cobros');

        return [
            'id' => $this->id,
            'venta_id' => $this->venta_id,
            'nombre' => $this->nombre,
            'fecha' => $this->fecha?->toDateString(),
            'porcentaje' => (float) $this->porcentaje,
            'monto' => (float) $this->monto,
            'bloqueado' => (bool) $this->bloqueado,
            'orden' => (int) $this->orden,
            'cobrado' => $this->when($conCobros, fn () => $this->cobrado()),
            'saldo' => $this->when($conCobros, fn () => $this->saldo()),
            // pendiente | vencido | parcial | pagado
            'estado' => $this->when($conCobros, fn () => $this->estado()),
            'estado_label' => $this->when($conCobros, fn () => $this->estadoLabel()),
            'vencido' => $this->when($conCobros, fn () => $this->vencido()),
            'tiene_cobros' => $this->when($conCobros, fn () => $this->cobros->isNotEmpty()),
        ];
    }
}
