<?php

namespace App\Services\Cuenta;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Lo que cada quien cambia de su propia cuenta: nombre y correo,
 * contraseña y firma registrada. Lo usan la pantalla de Perfil y la API.
 */
class MiPerfil
{
    public static function reglasDatos(?User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
        ];
    }

    public const REGLAS_CONTRASENA = [
        'current_password' => ['required', 'current_password'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];

    public const REGLAS_FIRMA = ['firma' => ['required', 'string']];

    public const MENSAJES_FIRMA = [
        'firma.required' => 'Traza tu firma en el recuadro antes de guardarla.',
    ];

    public const FIRMA_INVALIDA = 'La firma no es válida. Vuelve a trazarla e intenta de nuevo.';

    /** Cerrar la cuenta es definitivo: contraseña y escribir ELIMINAR. */
    public static function reglasEliminarCuenta(): array
    {
        return [
            'password' => ['required', 'current_password'],
            'confirm' => ['required', Rule::in(['ELIMINAR'])],
        ];
    }

    public const MENSAJES_ELIMINAR_CUENTA = [
        'confirm.in' => 'Debes escribir ELIMINAR para confirmar.',
    ];

    /**
     * Guarda nombre y correo. Si cambió el correo, hay que verificarlo de
     * nuevo: se marca sin verificar y se manda un código al nuevo.
     * Devuelve si cambió el correo.
     */
    public function actualizarDatos(User $user, array $data): bool
    {
        $correoCambio = $data['email'] !== $user->email;

        $user->fill($data);

        if (! $correoCambio) {
            $user->save();

            return false;
        }

        $user->email_verified_at = null;
        $user->save();

        $codigo = $user->generateVerificationCode();
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($codigo, $user->name));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el código tras cambiar el correo: '.$e->getMessage());
        }

        return true;
    }

    public function cambiarContrasena(User $user, string $nueva): void
    {
        $user->forceFill(['password' => $nueva])->save();
    }

    /**
     * Guarda la firma (data URL base64 de PNG/JPG, lo que produce el
     * lienzo). Devuelve false si no es una imagen válida; en ese caso no
     * se toca la que ya había.
     */
    public function guardarFirma(User $user, string $dataUrl): bool
    {
        $contenido = $this->decodificarFirma($dataUrl);

        if ($contenido === null) {
            return false;
        }

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

        return true;
    }

    /** Quita la firma registrada: vuelve a pedirse a mano en cada firma. */
    public function quitarFirma(User $user): void
    {
        if ($user->firma_path) {
            Storage::disk(config('filesystems.fotos_disk', 'public'))->delete($user->firma_path);
            $user->forceFill(['firma_path' => null])->save();
        }
    }

    /** Se valida el formato antes de escribir nada en disco. */
    private function decodificarFirma(string $dataUrl): ?string
    {
        if (! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $dataUrl, $match)) {
            return null;
        }

        $contenido = base64_decode($match[2], true);

        return $contenido === false || $contenido === '' ? null : $contenido;
    }
}
