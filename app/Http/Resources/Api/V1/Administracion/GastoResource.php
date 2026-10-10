<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Services\Administracion\GastosDeViaje;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un gasto suelto de un viático o de un viaje (TripExpense tiene las
 * mismas columnas).
 *
 * @mixin \App\Models\ViaticExpense
 */
class GastoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => GastosDeViaje::TIPOS[$this->type] ?? $this->type,
            'label' => $this->label,
            'amount' => (float) $this->amount,
            'icon' => $this->icon,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
