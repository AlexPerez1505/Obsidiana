<?php

namespace App\Http\Controllers\Api\V1\Ventas\Concerns;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Un cobro o una parcialidad solo se toca por la dirección de su propia
 * venta: así el permiso por asesor que ya revisó {venta} alcanza también a
 * lo que cuelga de ella.
 */
trait DeLaMismaVenta
{
    protected function asegurarDeLaVenta(Venta $venta, Model $hijo): void
    {
        if ((int) $hijo->venta_id !== (int) $venta->id) {
            // Como si no existiera: no se confirma que está en otra venta.
            throw (new ModelNotFoundException)->setModel($hijo::class, [$hijo->getKey()]);
        }
    }
}
