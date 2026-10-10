<?php

namespace App\Http\Resources\Api\V1\Rh;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Evento de empresa: descanso del equipo, día sin checar u horario
 * especial, para todos o para una selección (`user_ids`).
 *
 * @mixin \App\Models\CompanyEvent
 */
class EventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'tipo_label' => $this->tipoLabel(),
            'date_from' => $this->date_from?->toDateString(),
            'date_to' => $this->date_to?->toDateString(),
            'scope' => $this->scope,
            'user_ids' => $this->user_ids ? array_map('intval', $this->user_ids) : null,
            'time_in' => $this->time_in ? substr($this->time_in, 0, 5) : null,
            'time_out' => $this->time_out ? substr($this->time_out, 0, 5) : null,
            'tolerance_minutes' => $this->tolerance_minutes,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'creador' => UsuarioResource::make($this->whenLoaded('creador')),
            'created_at' => $this->created_at,
        ];
    }
}
