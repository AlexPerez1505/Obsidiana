<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * El rol Mantenimiento Externo solo entra a "Externo" e "Historial de
 * Servicios" dentro de Gestión de Servicios: nada más de Órdenes de
 * servicio, ni del resto del sistema.
 *
 * Se aplica a nivel de rutas (no solo escondiendo el menú) para que
 * tampoco entren tecleando la URL directo.
 */
class RestrictMantenimientoExternoAccess
{
    private const PERMITIDAS = [
        'gestion.servicios.externo',
        'gestion.servicios.externo.recepcion',
        'gestion.servicios.externo.recepcion.store',
        'gestion.servicios.historial',
        'gestion.servicios.historial.show',
        'qr.*',
        'dashboard',
        'dashboard.widgets.*',
        'account',
        'account.destroy',
        'account.sessions.destroyOthers',
        'profile.*',
        'logout',
        'login',
        'verification.*',
        'approval.pending',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (! $user || ! $routeName || $user->isAdmin() || ! $user->hasRole('mantenimiento_externo')) {
            return $next($request);
        }

        if (Str::is(self::PERMITIDAS, $routeName)) {
            return $next($request);
        }

        return redirect()->route('gestion.servicios.externo')
            ->with('status', 'Tu cuenta solo tiene acceso a Externo.');
    }
}
