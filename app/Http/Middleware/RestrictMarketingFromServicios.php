<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * El rol Marketing no entra a Gestión de Servicios.
 *
 * Se aplica a nivel de rutas (no solo escondiendo el menú) para que
 * tampoco entren tecleando la URL directo.
 */
class RestrictMarketingFromServicios
{
    private const BLOQUEADAS = [
        'gestion.servicios.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (! $user || ! $routeName || $user->isAdmin() || ! $user->hasRole('marketing')) {
            return $next($request);
        }

        if (! Str::is(self::BLOQUEADAS, $routeName)) {
            return $next($request);
        }

        return redirect()->route('dashboard')
            ->with('status', 'Tu cuenta no tiene acceso a Gestión de Servicios.');
    }
}
