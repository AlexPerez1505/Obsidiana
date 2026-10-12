<?php

namespace App\Http\Controllers\Api\V1\Cuenta;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Cuenta\MiPerfilResource;
use App\Services\Cuenta\MiPerfil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * El perfil propio: nombre, correo, contraseña y firma registrada. No pide
 * ningún permiso: cada quien edita lo suyo, igual que en la web.
 *
 * @tags Mi cuenta
 */
class PerfilController extends Controller
{
    public function __construct(private MiPerfil $perfil)
    {
    }

    /**
     * Ver mi perfil.
     *
     * Lo mismo que `/auth/yo` (roles y permisos de este token) más la
     * firma registrada.
     */
    public function show(Request $request): MiPerfilResource
    {
        return new MiPerfilResource($request->user());
    }

    /**
     * Editar nombre y correo.
     *
     * Si cambia el correo, queda sin verificar y se manda un código al
     * nuevo (`correo_por_verificar: true`). Hasta que se verifique desde
     * la web, la API responde 403 con `codigo: correo_no_verificado`.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate(MiPerfil::reglasDatos($user));

        $cambioCorreo = $this->perfil->actualizarDatos($user, $data);

        return (new MiPerfilResource($user))
            ->additional([
                'message' => $cambioCorreo
                    ? 'Cambiaste tu correo. Te enviamos un código para verificar el nuevo.'
                    : 'Perfil actualizado.',
                'correo_por_verificar' => $cambioCorreo,
            ])
            ->response();
    }

    /**
     * Cambiar contraseña.
     *
     * Pide la actual (`current_password`), la nueva (`password`, mínimo 8)
     * y su confirmación (`password_confirmation`). Las sesiones abiertas
     * siguen funcionando.
     */
    public function contrasena(Request $request): JsonResponse
    {
        $request->validate(MiPerfil::REGLAS_CONTRASENA);

        $this->perfil->cambiarContrasena($request->user(), $request->input('password'));

        return response()->json(['message' => 'Contraseña actualizada.']);
    }

    /**
     * Registrar firma.
     *
     * `firma` es la imagen como data URL base64 (`data:image/png;base64,…`
     * o JPG), lo que produce un lienzo de firma. Reemplaza a la anterior;
     * de ahí la cargan las pantallas que piden firma.
     */
    public function firma(Request $request): MiPerfilResource
    {
        $request->validate(MiPerfil::REGLAS_FIRMA, MiPerfil::MENSAJES_FIRMA);

        if (! $this->perfil->guardarFirma($request->user(), $request->input('firma'))) {
            throw ValidationException::withMessages(['firma' => MiPerfil::FIRMA_INVALIDA]);
        }

        return (new MiPerfilResource($request->user()))
            ->additional(['message' => 'Firma registrada. Ya se va a cargar sola cuando tengas que firmar.']);
    }

    /**
     * Quitar firma.
     *
     * A partir de ahí se vuelve a trazar a mano en cada firma.
     */
    public function quitarFirma(Request $request): JsonResponse
    {
        $this->perfil->quitarFirma($request->user());

        return response()->json(['message' => 'Se quitó tu firma registrada.']);
    }
}
