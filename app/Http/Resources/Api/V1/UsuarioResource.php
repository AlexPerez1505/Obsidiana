<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Un usuario tal como lo ve otro usuario: lo necesario para mostrarlo
 * (asesor de una venta, chofer de una ruta, autor de un movimiento…).
 * Sin datos de RH ni sueldos.
 *
 * @mixin \App\Models\User
 */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cargo' => $this->cargo ?: $this->position,
            'avatar_url' => $this->avatar
                ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->avatar)
                : null,
        ];
    }
}
