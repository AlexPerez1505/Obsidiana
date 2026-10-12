<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            abort(403, 'Acceso solo para administradores.');
        }

        // Por la API, además, el token debe tener acceso total: una llave
        // recortada a ciertos permisos no hereda lo que es solo del admin.
        $token = $request->user()->currentAccessToken();
        if ($token && ! $token->can('*')) {
            abort(403, 'Esta llave no tiene acceso total; lo de administrador no se le permite.');
        }

        return $next($request);
    }
}
