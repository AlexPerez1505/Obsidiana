<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Services\Administracion\Viajes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un viaje y lo que se ha gastado en él.
 *
 * @mixin \App\Models\Trip
 */
class ViajeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'usuario' => new UsuarioResource($this->whenLoaded('user')),
            'vehicle_id' => $this->vehicle_id,
            'vehicle_name' => $this->vehicle_name,
            'place' => $this->place,
            'status' => $this->status,
            'status_label' => Viajes::ESTADOS[$this->status] ?? $this->status,
            // La suma de sus gastos al momento.
            'total' => $this->total_computed,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'gastos' => GastoResource::collection($this->whenLoaded('expenses')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
