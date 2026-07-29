<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Cuenta baneada / desactivada: se cierra la sesión al instante.
        if ($user && $user->isBanned()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta fue desactivada. Contacta al administrador.',
            ]);
        }

        // Cuenta pendiente de aprobación por el administrador.
        if ($user && $user->isPending()) {
            return redirect()->route('approval.pending');
        }

        return $next($request);
    }
}
