<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Cuenta\MiPerfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Perfil propio. Lo que se guarda vive en MiPerfil, que comparte con la API.
 */
class ProfileController extends Controller
{
    public function __construct(private MiPerfil $perfil)
    {
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /**
     * Actualiza nombre y correo. Si cambia el correo, se vuelve a verificar.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(MiPerfil::reglasDatos($request->user()));

        if ($this->perfil->actualizarDatos($request->user(), $data)) {
            return redirect()->route('verification.notice')
                ->with('status', 'Cambiaste tu correo. Te enviamos un código para verificar el nuevo.');
        }

        return back()->with('status', 'Perfil actualizado.');
    }

    /**
     * Cambia la contraseña (pide la actual).
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate(MiPerfil::REGLAS_CONTRASENA);

        $this->perfil->cambiarContrasena($request->user(), $request->input('password'));

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
        $request->validate(MiPerfil::REGLAS_FIRMA, MiPerfil::MENSAJES_FIRMA);

        if (! $this->perfil->guardarFirma($request->user(), $request->input('firma'))) {
            return back()->withErrors(['firma' => MiPerfil::FIRMA_INVALIDA]);
        }

        return back()->with('status', 'Firma registrada. Ya se va a cargar sola cuando tengas que firmar.');
    }

    /** Quita la firma registrada: vuelve a pedirse a mano en cada firma. */
    public function destroyFirma(Request $request): RedirectResponse
    {
        $this->perfil->quitarFirma($request->user());

        return back()->with('status', 'Se quitó tu firma registrada.');
    }
}
