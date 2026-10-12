<?php

namespace App\Http\Resources\Api\V1\Ventas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El cliente de una venta o factura, lo necesario para mostrarlo y
 * contactarlo. El expediente completo está en /clientes/{id}.
 *
 * @mixin \App\Models\Customer
 */
class ClienteResumenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'telefono' => $this->telefono,
            'gmail' => $this->gmail,
            'rfc' => $this->rfc,
            'asesor_id' => $this->asesor_id,
            'publico_en_general' => $this->esPublicoEnGeneral(),
        ];
    }
}
