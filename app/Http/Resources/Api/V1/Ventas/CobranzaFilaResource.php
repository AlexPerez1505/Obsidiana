<?php

namespace App\Http\Resources\Api\V1\Ventas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una venta en el panorama de cobranza: cuánto se le exige, cuánto ha
 * pagado, cuánto debe y qué tan atrasada va. Viene de PanoramaCobranza::fila().
 */
class CobranzaFilaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $f = $this->resource;
        $venta = $f['venta'];

        return [
            'venta_id' => $venta->id,
            'folio' => $venta->folio,
            'customer_id' => $venta->customer_id,
            'cliente' => $f['cliente'],
            'seller_id' => $venta->seller_id,
            'asesor' => $f['asesor'],
            'productos' => $f['productos'],
            'modalidad' => $venta->modalidad,
            'estado' => $venta->estado,
            'created_at' => $venta->created_at,
            // Lo exigible: total del contrato si hay valor a cuenta.
            'total' => (float) $f['total'],
            'cobrado' => (float) $f['cobrado'],
            'saldo' => (float) $f['saldo'],
            'avance' => (int) $f['avance'],
            // pendiente | parcial | pagado
            'estado_pago' => $f['estado'],
            'proxima_fecha' => $f['proxima']?->toDateString(),
            'atrasada' => (bool) $f['atrasada'],
            // Días desde la parcialidad vencida más antigua.
            'dias_atraso' => (int) $f['dias_atraso'],
            'vencido_monto' => (float) $f['vencido_monto'],
        ];
    }
}
