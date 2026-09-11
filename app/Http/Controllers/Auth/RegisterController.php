<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Roles que alguien puede elegir para su propia cuenta al registrarse.
     * El de administrador nunca se autoasigna.
     */
    private function rolesElegibles()
    {
        return Role::where('is_active', true)
            ->where('name', '!=', User::ROL_ADMIN)
            ->orderBy('label')
            ->get();
    }

    public function create(): View
    {
        return view('auth.register', ['roles' => $this->rolesElegibles()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id'  => ['required', Rule::exists('roles', 'id')->where('is_active', true)->whereNot('name', User::ROL_ADMIN)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->roles()->attach($data['role_id']);

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
