<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Mail\VerificationCodeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Guarda la firma del usuario para no volver a trazarla cada vez.
     *
     * Se traza una sola vez aquí y de ahí la cargan las pantallas que piden
     * firma (entradas de inventario, salidas de almacén).
     */
    public function updateFirma(Request $request): RedirectResponse
    {
        $request->validate(['firma' => ['required', 'string']], [
            'firma.required' => 'Traza tu firma en el recuadro antes de guardarla.',
        ]);

        $contenido = $this->decodificarFirma($request->input('firma'));

        if ($contenido === null) {
            return back()->withErrors(['firma' => 'La firma no es válida. Vuelve a trazarla e intenta de nuevo.']);
        }

        $user = $request->user();
        $disco = config('filesystems.fotos_disk', 'public');
        $anterior = $user->firma_path;

        $path = 'usuarios/firmas/'.uniqid('firma_u'.$user->id.'_').'.png';
        Storage::disk($disco)->put($path, $contenido);

        $user->forceFill(['firma_path' => $path])->save();

        // La vieja se borra al final: si algo falla arriba, no se pierde la
        // que ya servía.
        if ($anterior && $anterior !== $path) {
            Storage::disk($disco)->delete($anterior);
        }

        return back()->with('status', 'Firma registrada. Ya se va a cargar sola cuando tengas que firmar.');
    }

    /** Quita la firma registrada: vuelve a pedirse a mano en cada firma. */
    public function destroyFirma(Request $request): RedirectResponse
    {
        $user = $request->user();
        $disco = config('filesystems.fotos_disk', 'public');

        if ($user->firma_path) {
            Storage::disk($disco)->delete($user->firma_path);
            $user->forceFill(['firma_path' => null])->save();
        }

        return back()->with('status', 'Se quitó tu firma registrada.');
    }

    /**
     * El lienzo manda la firma como data URL base64. Se valida el formato
     * antes de escribir nada en disco.
     */
    private function decodificarFirma(string $dataUrl): ?string
    {
        if (! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $dataUrl, $match)) {
            return null;
        }

        $contenido = base64_decode($match[2], true);

        return $contenido === false || $contenido === '' ? null : $contenido;
    }
}
