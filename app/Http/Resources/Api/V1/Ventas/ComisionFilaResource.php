<?php

namespace App\Http\Resources\Api\V1\Ventas;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lo de un asesor en el mes: cuánto vendió, cuánto se cobró de sus
 * ventas, su porcentaje, la comisión que le toca y lo que ya se le pagó.
 * Viene de Comisiones::filasDelPeriodo().
 */
class ComisionFilaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $f = $this->resource;

        return [
            'user_id' => $f['asesor']->id,
            'asesor' => new UsuarioResource($f['asesor']),
            'ventas' => (int) $f['ventas'],
            'vendido' => (float) $f['vendido'],
            'cobrado' => (float) $f['cobrado'],
            // null = todavía no se le define porcentaje.
            'porcentaje' => $f['porcentaje'],
            // Lo vendido o lo cobrado, según la base que se pidió.
            'base_monto' => (float) $f['base_monto'],
            'comision' => (float) $f['comision'],
            // Desglose con las reglas de config/comisiones.php.
            'comision_endoscopia' => (float) ($f['comision_endoscopia'] ?? 0),
            'comision_otros' => (float) ($f['comision_otros'] ?? 0),
            'comision_congreso' => (float) ($f['comision_congreso'] ?? 0),
            'ventas_congreso' => (int) ($f['ventas_congreso'] ?? 0),
            'pagado' => (float) $f['pagado'],
            'pendiente' => (float) $f['pendiente'],
        ];
    }
}
