<?php

namespace App\Http\Resources\Api\V1\Comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un renglón de la cotización. Nombre, marca, modelo, imagen y precio se
 * congelan al guardar: si el producto cambia después, la cotización ya
 * emitida no cambia.
 *
 * @mixin \App\Models\CotizacionItem
 */
class CotizacionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cotizacion_id' => $this->cotizacion_id,
            /** equipo | paquete | producto */
            'tipo_item' => $this->tipo_item,
            'equipo_id' => $this->equipo_id,
            'paquete_id' => $this->paquete_id,
            'producto_id' => $this->producto_id,
            'nombre' => $this->nombre,
            'modelo' => $this->modelo,
            'marca' => $this->marca,
            // Ya se guarda como dirección completa.
            'imagen_url' => $this->imagen,
            'cantidad' => (int) $this->cantidad,
            'precio_unitario' => (float) $this->precio_unitario,
            'sobreprecio' => (float) $this->sobreprecio,
            'es_regalo' => (bool) $this->es_regalo,
            // (precio_unitario + sobreprecio) × cantidad; 0 si es regalo.
            'importe' => $this->importe(),
            'orden' => (int) $this->orden,
        ];
    }
}
