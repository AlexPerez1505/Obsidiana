<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TokenApi;
use App\Support\CatalogoPermisos;
use Illuminate\Http\Request;

/**
 * El usuario autenticado: sus datos, su estado y lo que puede hacer con
 * ESTE token (los permisos ya vienen recortados a lo que permite la llave).
 *
 * @mixin \App\Models\User
 */
class PerfilResource extends UsuarioResource
{
    public function toArray(Request $request): array
    {
        $token = $this->currentAccessToken();

        return parent::toArray($request) + [
            'is_admin' => $this->isAdmin(),
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'roles' => $this->roles()->where('is_active', true)->get(['name', 'label'])
                ->map(fn ($r) => ['name' => $r->name, 'label' => $r->label])->values(),
            'permisos' => static::permisosDe($this->resource),
            'token' => $token instanceof TokenApi ? [
                'id' => $token->id,
                'name' => $token->name,
                'tipo' => $token->tipo,
                'acceso_total' => $token->accesoTotal(),
                'expires_at' => $token->expires_at,
            ] : null,
        ];
    }

    /**
     * Las llaves del catálogo que el usuario puede usar ahora mismo.
     * Pasa por los Gates, así que respeta admin y el recorte del token.
     *
     * @return list<string>
     */
    public static function permisosDe($user): array
    {
        return collect(CatalogoPermisos::llaves())
            ->filter(fn (string $p) => $user->can($p))
            ->values()
            ->all();
    }
}
