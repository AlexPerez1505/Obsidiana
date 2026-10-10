<?php

namespace App\Http\Resources\Api\V1\Servicios;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Invitación de un solo uso para registrar una orden sin cuenta. `link`
 * es lo que se le manda a la persona (WhatsApp, correo); el token va
 * dentro del enlace, igual que lo entrega la web.
 *
 * @mixin \App\Models\ServiceInvitation
 */
class InvitacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'link' => route('public.nueva_orden', $this->resource),
            'token' => $this->token,
            'status' => $this->status ?? 'pending',
            'invited_by' => $this->invited_by,
            'expires_at' => $this->expires_at,
            'used_at' => $this->used_at,
            'created_at' => $this->created_at,
        ];
    }
}
