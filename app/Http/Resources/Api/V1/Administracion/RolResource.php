<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Services\Administracion\RolesYPermisos;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un rol y lo que puede hacer. El de administrador sale con
 * `intocable: true`: siempre puede todo y no se edita ni se borra.
 *
 * @mixin \App\Models\Role
 */
class RolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'intocable' => $this->name === RolesYPermisos::INTOCABLE,
            'permisos' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')->values()),
            'users_count' => $this->whenCounted('users'),
            'usuarios' => UsuarioResource::collection($this->whenLoaded('users')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
