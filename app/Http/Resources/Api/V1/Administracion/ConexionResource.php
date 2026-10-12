<?php

namespace App\Http\Resources\Api\V1\Administracion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un inicio de sesión registrado (web o app).
 *
 * @mixin \App\Models\LoginLog
 */
class ConexionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'location' => $this->location,
            'browser' => $this->browser,
            'platform' => $this->platform,
            'user_agent' => $this->user_agent,
            'logged_at' => $this->logged_at?->toIso8601String(),
        ];
    }
}
