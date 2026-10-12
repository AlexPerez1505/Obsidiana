<?php

namespace App\Services\Administracion;

use App\Models\User;

/**
 * ¿Esta persona actúa como administrador en esta petición?
 *
 * En la web basta con isAdmin(). Por la API, además, el token tiene que
 * ser de acceso total: una llave recortada a ciertos permisos no hereda lo
 * que es solo del admin, aunque su dueño lo sea (igual que el middleware
 * `admin`). Las revisiones de "el dueño o un admin" que viven dentro del
 * código (agenda, viáticos, materiales…) pasan por aquí para que valgan
 * igual en las dos entradas.
 */
final class Administrador
{
    public static function pleno(?User $user): bool
    {
        if (! $user || ! $user->isAdmin()) {
            return false;
        }

        $token = $user->currentAccessToken();

        return ! $token || $token->can('*');
    }
}
