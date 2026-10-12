<?php

namespace App\Http\Resources\Api\V1\Logistica\Concerns;

/**
 * El disco público da rutas relativas a propósito (el sistema se abre
 * desde varias direcciones). Una app no tiene "página actual" contra la
 * cual resolverlas, así que se completan con el host por el que llegó la
 * petición. Si el disco ya da una URL absoluta (S3, CDN) se deja igual.
 */
trait UrlCompleta
{
    protected static function urlCompleta(?string $url): ?string
    {
        return $url ? url($url) : null;
    }
}
