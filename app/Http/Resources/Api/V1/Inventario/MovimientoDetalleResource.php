<?php

namespace App\Http\Resources\Api\V1\Inventario;

use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use Illuminate\Http\Request;

/**
 * El movimiento con lo que enseña su pantalla de detalle: el producto y,
 * si es una salida por venta, la orden de salida que controla su entrega.
 *
 * @mixin InventoryMovement
 */
class MovimientoDetalleResource extends MovimientoResource
{
    public function __construct($resource, private ?OrdenSalida $ordenSalida = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $producto = $this->producto();
        $orden = $this->ordenSalida;

        return parent::toArray($request) + [
            'producto' => $producto ? new ProductoResource($producto) : null,
            'orden_salida' => $orden ? [
                'id' => $orden->id,
                'folio' => $orden->folio,
                'estado' => $orden->estado,
                'venta_id' => $orden->venta_id,
                'venta_folio' => $orden->venta?->folio,
            ] : null,
        ];
    }
}
