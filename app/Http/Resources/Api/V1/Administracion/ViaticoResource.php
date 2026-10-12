<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Services\Administracion\Viaticos;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Un viático con sus totales, fotos del ticket y gastos.
 *
 * `ticket_photos` son las rutas tal como se guardan: son las que se mandan
 * en `quitar_fotos` para quitar una al editar. `ticket_photos_url` son las
 * mismas, para mostrarlas.
 *
 * @mixin \App\Models\Viatic
 */
class ViaticoResource extends JsonResource
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
            'tolls' => (float) $this->tolls,
            'fuel' => (float) $this->fuel,
            'meals' => (float) $this->meals,
            'lodging' => (float) $this->lodging,
            'additional' => (float) $this->additional,
            // Con gastos sueltos es su suma; si no hay, la de los totales.
            'total' => $this->total_computed,
            'description' => $this->description,
            'expense_date' => $this->expense_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => Viaticos::ESTADOS[$this->status] ?? $this->status,
            'ticket_photos' => array_values($this->ticket_photos ?? []),
            'ticket_photos_url' => $this->ticketPhotoUrls(),
            // La foto única de antes de que se permitieran varias.
            'ticket_photo_url' => $this->ticket_photo ? Storage::disk('public')->url($this->ticket_photo) : null,
            'gastos' => GastoResource::collection($this->whenLoaded('expenses')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
