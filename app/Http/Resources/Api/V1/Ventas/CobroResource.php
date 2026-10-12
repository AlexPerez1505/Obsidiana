<?php

namespace App\Http\Resources\Api\V1\Ventas;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Un cobro: dinero que ya entró. Un cobro es un solo recibo aunque se haya
 * repartido entre varias parcialidades (ver `aplicaciones`).
 *
 * @mixin \App\Models\Cobro
 */
class CobroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $conAplicaciones = $this->relationLoaded('aplicaciones');

        return [
            'id' => $this->id,
            'venta_id' => $this->venta_id,
            // La primera parcialidad a la que se aplicó.
            'venta_pago_id' => $this->venta_pago_id,
            'folio' => $this->folio,
            'fecha' => $this->fecha?->toDateString(),
            'monto' => (float) $this->monto,
            'metodo' => $this->metodo,
            'metodo_label' => $this->metodoLabel(),
            'referencia' => $this->referencia,
            'nota' => $this->nota,
            'registrado_por' => $this->registrado_por,
            'aplicado' => $this->when($conAplicaciones, fn () => $this->aplicado()),
            'sin_aplicar' => $this->when($conAplicaciones, fn () => $this->sinAplicar()),
            'aplicaciones' => $this->whenLoaded('aplicaciones', fn () => $this->aplicaciones->map(fn ($a) => [
                'venta_pago_id' => $a->venta_pago_id,
                'nombre' => $a->relationLoaded('pago') ? $a->pago?->nombre : null,
                'monto' => (float) $a->monto,
            ])->values()),
            'evidencias' => $this->whenLoaded('evidencias', fn () => $this->evidencias->map(fn ($e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'archivo_url' => Storage::disk('public')->url($e->archivo),
            ])->values()),
            'registrado_por_usuario' => new UsuarioResource($this->whenLoaded('registradoPor')),
            'recibo_url' => route('api.v1.ventas.cobros.recibo', [$this->venta_id, $this->id]),
            'created_at' => $this->created_at,
        ];
    }
}
