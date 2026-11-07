<?php

namespace App\Services\WhatsApp;

/**
 * Teléfonos para WhatsApp: la Cloud API los quiere en E.164 sin "+".
 * Un número de 10 dígitos se toma como de México (52).
 */
class Telefono
{
    public static function e164(?string $telefono): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $telefono) ?? '';

        if (strlen($d) === 10) {
            return '52'.$d;
        }
        // Formato viejo de celular mexicano: 52 1 + 10 dígitos.
        if (strlen($d) === 13 && str_starts_with($d, '521')) {
            return '52'.substr($d, 3);
        }
        if (strlen($d) >= 11 && strlen($d) <= 15) {
            return $d;
        }

        return null;
    }

    /** Solo los últimos 4 dígitos visibles: para pantallas y para los logs. */
    public static function ocultar(?string $telefono): string
    {
        $d = preg_replace('/\D+/', '', (string) $telefono) ?? '';

        return $d === '' ? '—' : str_repeat('•', max(0, strlen($d) - 4)).substr($d, -4);
    }

    /** ¿Es el mismo número aunque venga con otro formato o lada? (últimos 10 dígitos) */
    public static function mismo(?string $a, ?string $b): bool
    {
        $x = substr(preg_replace('/\D+/', '', (string) $a) ?? '', -10);
        $y = substr(preg_replace('/\D+/', '', (string) $b) ?? '', -10);

        return $x !== '' && $x === $y;
    }
}
