<?php

namespace App\Http\Resources\Api\V1\Comercial;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un seguimiento programado con un cliente o prospecto. El día de `fecha`
 * el responsable (`user_id`) recibe el aviso en la campana y por correo.
 *
 * @mixin \App\Models\ClienteSeguimiento
 */
class SeguimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            /** llamada | cotizacion | visita | correo | demo | otro */
            'tipo' => $this->tipo,
            'tipo_label' => $this->tipoLabel(),
            'fecha' => $this->fecha?->toDateString(),
            'nota' => $this->nota,
            'notificado_en' => $this->notificado_en,
            'hecho_en' => $this->hecho_en,
            'hecho_por' => $this->hecho_por,
            'resultado' => $this->resultado,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            /** hecho | vencido | hoy | pendiente */
            'estado' => match (true) {
                $this->hecho() => 'hecho',
                $this->vencido() => 'vencido',
                $this->esHoy() => 'hoy',
                default => 'pendiente',
            },
            // "Hoy", "Mañana", "Vencido hace 3 días"…, como en la ficha web.
            'cuando' => $this->cuando(),

            'responsable' => UsuarioResource::make($this->whenLoaded('responsable')),
            'hecho_por_usuario' => UsuarioResource::make($this->whenLoaded('hechoPor')),
        ];
    }
}
