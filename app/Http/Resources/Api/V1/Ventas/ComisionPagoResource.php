<?php

namespace App\Http\Resources\Api\V1\Ventas;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un pago de comisión a un asesor por un mes. No se edita: si se capturó
 * mal, se elimina y se registra de nuevo.
 *
 * @mixin \App\Models\ComisionPago
 */
class ComisionPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            // Mes al que corresponde, "2026-09".
            'periodo' => $this->periodo,
            'monto' => (float) $this->monto,
            'fecha' => $this->fecha?->toDateString(),
            // cobrado | vendido
            'base' => $this->base,
            'nota' => $this->nota,
            'registrado_por' => $this->registrado_por,
            'asesor' => new UsuarioResource($this->whenLoaded('asesor')),
            'registrado_por_usuario' => new UsuarioResource($this->whenLoaded('registradoPor')),
            'created_at' => $this->created_at,
        ];
    }
}
