<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data);

        // Generar y enviar el código de verificación por correo.
        $code = $user->generateVerificationCode();
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($code, $user->name));
        } catch (\Throwable $e) {
            // No romper el registro si el SMTP falla; el usuario puede reenviar.
            Log::error('No se pudo enviar el código de verificación: '.$e->getMessage());
        }

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('status', 'Te enviamos un código de verificación a tu correo.');
    }
}
