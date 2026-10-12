<?php

namespace App\Http\Resources\Api\V1\Ventas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un renglón de la venta, con nombre, marca y modelo congelados al vender
 * y los números de serie de las piezas que salieron.
 *
 * @mixin \App\Models\VentaItem
 */
class VentaItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'venta_id' => $this->venta_id,
            'tipo_item' => $this->tipo_item,
            'equipo_id' => $this->equipo_id,
            'paquete_id' => $this->paquete_id,
            'producto_id' => $this->producto_id,
            'nombre' => $this->nombre,
            'modelo' => $this->modelo,
            'marca' => $this->marca,
            // Se guarda ya como URL completa al momento de vender.
            'imagen_url' => $this->imagen,
            'cantidad' => (int) $this->cantidad,
            'no_series' => $this->no_series,
            'precio_unitario' => (float) $this->precio_unitario,
            'sobreprecio' => (float) $this->sobreprecio,
            'es_regalo' => (bool) $this->es_regalo,
            'importe' => $this->importe(),
            'orden' => (int) $this->orden,
        ];
    }
}
