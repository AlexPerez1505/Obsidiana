<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    /**
     * Pantalla para ingresar el código.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify');
    }

    /**
     * Verifica el código ingresado.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if (! $user->verificationCodeMatches($request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'El código es incorrecto o ha expirado.',
            ]);
        }

        $user->confirmEmailVerification();

        // Si aún no lo aprueba un administrador, a la sala de espera.
        if ($user->isPending()) {
            return redirect()->route('approval.pending')
                ->with('status', '¡Correo verificado! Tu cuenta está pendiente de aprobación.');
        }

        return redirect()->route('dashboard')
            ->with('status', '¡Tu correo fue verificado correctamente!');
    }

    /**
     * Reenvía un nuevo código (con throttle de 60s).
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if (($wait = $user->secondsUntilResend()) > 0) {
            return back()->with('status', "Espera {$wait} segundos antes de pedir otro código.");
        }

        $code = $user->generateVerificationCode();
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($code, $user->name));
        } catch (\Throwable $e) {
            Log::error('No se pudo reenviar el código: '.$e->getMessage());
            return back()->with('status', 'No se pudo enviar el correo. Revisa la configuración SMTP en el .env.');
        }

        return back()->with('status', 'Te enviamos un nuevo código a tu correo.');
    }
}
