<?php

namespace App\Http\Resources\Api\V1\Comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un producto que se puede agregar a una cotización o venta. Los campos
 * `tipo_item`, `producto_id`, `nombre`, `modelo`, `marca`, `precio` (como
 * `precio_unitario`) e `imagen_url` (como `imagen`) son los que lleva el
 * renglón al guardar.
 *
 * @mixin \App\Models\Producto
 */
class ProductoCotizableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** producto */
            'tipo_item' => 'producto',
            'producto_id' => $this->id,
            'nombre' => $this->nombreVisible(),
            'modelo' => $this->modelo,
            'marca' => $this->marca,
            'precio' => (float) $this->precio,
            'imagen_url' => $this->imagen_path ? asset('storage/'.$this->imagen_path) : null,
            // Ya excluye lo vendido y lo que sigue en hojalatería o
            // mantenimiento: solo cuenta lo que de verdad se puede entregar.
            'stock' => (int) $this->stock,
            'piezas' => $this->serialesDisponibles->map(fn ($s) => [
                'id' => $s->id,
                'codigo' => $s->codigo,
                'no_serie' => $s->no_serie,
                'condicion' => $s->condicion,
                // Se puede vender, pero no está en el almacén sino en este congreso.
                'congreso' => $s->congress?->nombre,
            ])->values(),
            // Si tiene ficha técnica, conviene adjuntarla (en `fichas`) al agregarlo.
            'ficha' => $this->fichaTecnica ? [
                'id' => $this->fichaTecnica->id,
                'titulo' => $this->fichaTecnica->titulo,
            ] : null,
        ];
    }
}
