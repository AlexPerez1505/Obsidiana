<?php

namespace App\Http\Controllers;

use App\Services\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Panel de la cuenta: datos + historial de conexiones + sesiones activas.
     */
    public function show(Request $request, SessionManager $sessions): View
    {
        $user = $request->user();

        return view('account.show', [
            'user'           => $user,
            'logs'           => $user->loginLogs()->limit(30)->get(),
            'activeSessions' => $sessions->activeSessionsFor($user->id, $request->session()->getId()),
        ]);
    }

    /**
     * Pantalla de espera mientras el administrador aprueba la cuenta.
     */
    public function pending(Request $request): View|RedirectResponse
    {
        // Si ya fue aprobada, mándalo al dashboard.
        if ($request->user()->isApproved()) {
            return redirect()->route('dashboard');
        }

        return view('account.pending', ['user' => $request->user()]);
    }

    /**
     * Cierra todas las demás sesiones (deja solo la actual).
     */
    public function destroyOtherSessions(Request $request, SessionManager $sessions): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $closed = $sessions->destroyOthersFor($request->user()->id, $request->session()->getId());

        return back()->with('status', "Se cerraron {$closed} sesión(es) en otros dispositivos.");
    }

    /**
     * Cerrar cuenta (eliminar): requiere confirmar la contraseña.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirm'  => ['required', Rule::in(['ELIMINAR'])],
        ], [
            'confirm.in' => 'Debes escribir ELIMINAR para confirmar.',
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete(); // Los login_logs se eliminan en cascada.

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Tu cuenta fue eliminada.');
    }
}
