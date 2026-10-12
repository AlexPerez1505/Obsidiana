<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Services\Administracion\AgendaAdministrativa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Una cita de la agenda administrativa.
 *
 * @mixin \App\Models\AgendaEvent
 */
class AgendaEventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'event_type' => $this->event_type,
            'type_label' => AgendaAdministrativa::TIPOS[$this->event_type] ?? $this->event_type,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'start_time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
            'duration_minutes' => $this->duration_minutes !== null ? (int) $this->duration_minutes : null,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'status_label' => AgendaAdministrativa::ESTADOS[$this->status] ?? $this->status,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'participants' => array_values(array_map('intval', $this->participants ?? [])),
            'created_by' => $this->created_by,
            'creador' => new UsuarioResource($this->whenLoaded('creator')),
            'updated_by' => $this->updated_by,
            // Si quien pregunta la puede reprogramar o borrar.
            'puede_modificar' => $request->user()
                ? app(AgendaAdministrativa::class)->puedeModificar($this->resource, $request->user())
                : false,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
