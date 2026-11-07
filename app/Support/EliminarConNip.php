<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Eliminar una cotización o una venta: durante las primeras 72 horas desde
 * que se creó basta con confirmar; después hay que escribir el NIP de
 * aprobación (o la contraseña, si el usuario no tiene NIP), igual que al
 * cancelar una venta. Lo usan la web y la API.
 */
class EliminarConNip
{
    public const HORAS = 72;

    public static function requiere(Model $doc): bool
    {
        return $doc->created_at !== null && $doc->created_at->lte(now()->subHours(self::HORAS));
    }

    /** Horas que le quedan para eliminarse sin NIP (0 si ya lo pide). */
    public static function horasRestantes(Model $doc): int
    {
        if (! $doc->created_at || self::requiere($doc)) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInMinutes($doc->created_at->copy()->addHours(self::HORAS)) / 60));
    }

    /** Lanza ValidationException (campo "nip") si hace falta el NIP y no es correcto. */
    public static function exigir(Model $doc, User $user, ?string $nip): void
    {
        if (! self::requiere($doc)) {
            return;
        }

        $nip = (string) $nip;
        $conPin = (bool) $user->approval_pin_hash;

        if ($nip === '') {
            throw ValidationException::withMessages([
                'nip' => 'Pasaron más de '.self::HORAS.' horas desde que se creó: escribe tu '.($conPin ? 'NIP' : 'contraseña').' para eliminarla.',
            ]);
        }

        $valido = $conPin ? $user->checkApprovalPin($nip) : Hash::check($nip, $user->password);

        if (! $valido) {
            throw ValidationException::withMessages(['nip' => $conPin ? 'NIP incorrecto.' : 'Contraseña incorrecta.']);
        }
    }
}
