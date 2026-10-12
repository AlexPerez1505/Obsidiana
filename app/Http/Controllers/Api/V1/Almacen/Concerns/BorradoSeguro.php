<?php

namespace App\Http\Controllers\Api\V1\Almacen\Concerns;

use Illuminate\Database\QueryException;

/**
 * Borrar algo del catálogo que otra tabla todavía usa (p. ej. un modelo
 * registrado en un servicio) truena en la base. En la web eso es un error
 * 500; en la API se contesta 409 con un porqué, para que la app lo diga.
 */
trait BorradoSeguro
{
    protected function borrarSinRomper(callable $borrar, string $enUso): mixed
    {
        try {
            return $borrar();
        } catch (QueryException $e) {
            // 23000: violación de integridad (una llave foránea lo detiene).
            if ((string) $e->getCode() === '23000') {
                abort(409, $enUso);
            }

            throw $e;
        }
    }
}
