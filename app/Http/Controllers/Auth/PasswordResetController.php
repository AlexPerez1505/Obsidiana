<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Paso 1: formulario para pedir el código (ingresar correo).
     */
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Paso 1 (POST): genera y envía el código al correo.
     */
    public function sendCode(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        // Solo enviamos si existe, pero respondemos igual para no revelar cuentas.
        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => Carbon::now()],
            );

            try {
                Mail::to($email)->send(new PasswordResetCodeMail($code, $user->name));
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar el código de recuperación: '.$e->getMessage());
            }
        }

        return redirect()->route('password.reset', ['email' => $email])
            ->with('status', 'Si el correo existe, te enviamos un código para restablecer la contraseña.');
    }

    /**
     * Paso 2: formulario para ingresar código + nueva contraseña.
     */
    public function resetForm(Request $request): View
    {
        return view('auth.reset-password', [
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Paso 2 (POST): valida el código y actualiza la contraseña.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'code'     => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->input('email'))
            ->first();

        $expired = ! $record || Carbon::parse($record->created_at)->addMinutes(15)->isPast();

        if ($expired || ! Hash::check($request->input('code'), $record->token)) {
            throw ValidationException::withMessages([
                'code' => 'El código es incorrecto o ha expirado.',
            ]);
        }

        $user = User::where('email', $request->input('email'))->firstOrFail();
        $user->forceFill(['password' => $request->input('password')])->save();

        // Invalidar el código usado.
        DB::table('password_reset_tokens')->where('email', $request->input('email'))->delete();

        return redirect()->route('login')
            ->with('status', 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.');
    }
}
