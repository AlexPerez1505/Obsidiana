<?php

namespace App\Http\Middleware;

use App\Support\ObsVivo;
use Closure;
use Illuminate\Http\Request;

/**
 * Cualquier cambio que salió bien (POST/PUT/PATCH/DELETE con respuesta
 * menor a 400), de la web o de la API, avisa a todas las pantallas abiertas
 * para que se actualicen solas. Cubre también lo que no pasa por eventos de
 * modelo (updates masivos, DB::table).
 */
class ObsVivoTocar
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->isMethodSafe()
            && $response->getStatusCode() < 400
            && ! $request->is(...ObsVivo::IGNORAR)) {
            ObsVivo::tocar();
        }

        return $response;
    }
}
