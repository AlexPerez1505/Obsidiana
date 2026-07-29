<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, LoginLogger $logger): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        $user = Auth::user();

        // Cuenta baneada / desactivada: no se permite el acceso.
        if ($user->isBanned()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta fue desactivada. Contacta al administrador.',
            ]);
        }

        $request->session()->regenerate();

        // Registrar el inicio de sesión (de dónde se conecta).
        $logger->record($user, $request);

        // Si el correo no está verificado, mandarlo a la pantalla de verificación.
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Si aún no lo aprueba un administrador, a la sala de espera.
        if ($user->isPending()) {
            return redirect()->route('approval.pending');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente.');
    }
}
