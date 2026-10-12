<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Documentos que cada asesor ve solo si son suyos, salvo permiso.
 *
 * El modelo que lo usa define PERMISO_VER_TODAS (la llave del catálogo
 * que abre el listado completo) y guarda al asesor en `seller_id`. El
 * administrador siempre pasa, porque el Gate lo resuelve antes.
 */
trait VisiblePorAsesor
{
    /** Solo lo que este usuario puede ver. */
    public function scopeVisiblesPara(Builder $query, User $user): Builder
    {
        if ($user->can(static::PERMISO_VER_TODAS)) {
            return $query;
        }

        return $query->where('seller_id', $user->id);
    }

    /** ¿Este documento en particular le aparece a este usuario? */
    public function visiblePara(User $user): bool
    {
        return $user->can(static::PERMISO_VER_TODAS) || (int) $this->seller_id === (int) $user->id;
    }
}
