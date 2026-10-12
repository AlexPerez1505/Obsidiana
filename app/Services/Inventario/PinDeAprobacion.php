<?php

namespace App\Services\Inventario;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Confirmación de las acciones que alteran evidencia (borrar una entrada,
 * corregir el serial de una pieza).
 *
 * Si el usuario ya configuró un PIN de aprobación, se usa ese; si no, se
 * acepta su contraseña normal para no dejarlo sin forma de confirmar.
 */
class PinDeAprobacion
{
    public const ERROR = 'PIN o contraseña incorrecta.';

    public static function valido(User $user, string $clave): bool
    {
        return $user->approval_pin_hash
            ? $user->checkApprovalPin($clave)
            : Hash::check($clave, $user->password);
    }
}
