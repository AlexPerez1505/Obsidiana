<?php

namespace App\Http\Resources\Api\V1\Servicios;

use Illuminate\Http\Request;

/**
 * La orden completa (lo que muestra la ficha de aprobación y la de
 * mantenimiento): equipo con evidencias, refacciones, firma de entrada y
 * todos los pasos recorridos.
 *
 * @mixin \App\Models\Service
 */
class ServicioDetalleResource extends ServicioResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            // Firma de quien entregó el equipo (data URL de imagen).
            'signature' => $this->signature,
            'service_equipment' => new EquipoDeServicioResource($this->whenLoaded('serviceEquipment')),
            'spare_parts' => $this->whenLoaded('spareParts', fn () => $this->spareParts->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'cantidad' => (int) $p->cantidad,
                'precio_unitario' => (float) $p->precio_unitario,
                'subtotal' => (float) $p->subtotal,
            ])->values()),
            'service_trackings' => SeguimientoResource::collection($this->whenLoaded('serviceTrackings')),
        ];
    }
}
