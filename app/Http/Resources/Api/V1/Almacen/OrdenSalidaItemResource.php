<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una partida de la orden de salida y lo que ya se le hizo.
 *
 * @mixin \App\Models\OrdenSalidaItem
 */
class OrdenSalidaItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orden_salida_id' => $this->orden_salida_id,
            'venta_item_id' => $this->venta_item_id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'no_series' => $this->no_series,
            'requiere_emplayado' => $this->requiere_emplayado,
            'preparado' => $this->preparado,
            'preparado_en' => $this->preparado_en,
            'emplayado' => $this->emplayado,
            'emplayado_en' => $this->emplayado_en,
            'observaciones' => $this->observaciones,
            'orden' => $this->orden,
            'completo' => $this->completo(),
            // Si el equipo se vendió en un congreso, nunca volvió al anaquel:
            // almacén no tiene que buscarlo.
            'congreso_de_origen' => $this->when(
                $this->relationLoaded('ventaItem'),
                fn () => $this->congresoDeOrigen()
            ),
        ];
    }
}
