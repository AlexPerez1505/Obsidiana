<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * El rol Mantenimiento no ve nada de comercial, inventario ni marketing:
 * solo su tablero y Órdenes de servicio.
 *
 * Se aplica a nivel de rutas (no solo escondiendo el menú) para que
 * tampoco entren tecleando la URL directo.
 */
class RestrictMantenimientoAccess
{
    /** Rutas a las que sí puede entrar, además de las públicas/de sesión. */
    private const PERMITIDAS = [
        'mantenimiento.*',
        'gestion.servicios.*',
        'qr.*',
        'dashboard.widgets.*', // no aplica: no tiene tablero general, pero no hace daño dejarlo
        'account',
        'account.destroy',
        'account.sessions.destroyOthers',
        'profile.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (! $user || ! $routeName || $user->isAdmin() || ! $user->hasRole('mantenimiento')) {
            return $next($request);
        }

        if (Str::is(self::PERMITIDAS, $routeName)) {
            return $next($request);
        }

        return redirect()->route('mantenimiento.dashboard')
            ->with('status', 'Tu cuenta solo tiene acceso a Órdenes de servicio.');
    }
}
