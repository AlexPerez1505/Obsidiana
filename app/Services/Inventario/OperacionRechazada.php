<?php

namespace App\Services\Inventario;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Una regla de inventario dijo que no porque choca con el estado del
 * registro: una entrada con piezas ya vendidas, una unidad que ya salió…
 *
 * No es un dato mal capturado (eso es ValidationException, un 422): el
 * usuario no puede arreglarlo cambiando el formulario. Por eso es un 409.
 *
 * La web la atrapa y regresa a la pantalla con el error en `campo`, como
 * siempre lo hizo. En /api nadie la atrapa y, como es una HttpException,
 * sale sola como el JSON de errores de la API.
 */
class OperacionRechazada extends HttpException
{
    public function __construct(public readonly string $campo, string $mensaje)
    {
        parent::__construct(409, $mensaje);
    }
}
