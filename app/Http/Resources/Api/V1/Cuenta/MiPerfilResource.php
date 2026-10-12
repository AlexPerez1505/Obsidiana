<?php

namespace App\Http\Resources\Api\V1\Cuenta;

use App\Http\Resources\Api\V1\PerfilResource;
use Illuminate\Http\Request;

/**
 * El perfil propio: lo de /auth/yo más la firma registrada.
 *
 * @mixin \App\Models\User
 */
class MiPerfilResource extends PerfilResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'position' => $this->position,
            // La firma que se carga sola en entradas y salidas de almacén.
            'tiene_firma' => $this->tieneFirma(),
            'firma_url' => $this->firmaUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
