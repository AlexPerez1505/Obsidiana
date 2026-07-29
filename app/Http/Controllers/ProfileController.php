<?php

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /**
     * Actualiza nombre y correo. Si cambia el correo, se vuelve a verificar.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $emailChanged = $data['email'] !== $user->email;

        $user->fill($data);

        if ($emailChanged) {
            // El nuevo correo debe verificarse de nuevo.
            $user->email_verified_at = null;
            $user->save();

            $code = $user->generateVerificationCode();
            try {
                Mail::to($user->email)->send(new VerificationCodeMail($code, $user->name));
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar el código tras cambiar el correo: '.$e->getMessage());
            }

            return redirect()->route('verification.notice')
                ->with('status', 'Cambiaste tu correo. Te enviamos un código para verificar el nuevo.');
        }

        $user->save();

        return back()->with('status', 'Perfil actualizado.');
    }

    /**
     * Cambia la contraseña (pide la actual).
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => $request->input('password'),
        ])->save();

        return back()->with('status', 'Contraseña actualizada.');
    }
}
