<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\EmployeeBiometric;
use App\Models\User;
use App\Services\Asistencia\RegistroDeHuellas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lectores de huella y registro de huellas de los empleados. */
class BiometriaController extends Controller
{
    public function __construct(private RegistroDeHuellas $huellas)
    {
    }

    public function index(): View
    {
        $dispositivos = BiometricDevice::withCount(['punches as checadas_hoy' => fn ($q) => $q->whereDate('work_date', today())])->orderBy('name')->get();

        $inscripciones = BiometricEnrollment::with(['user', 'device'])
            ->whereIn('status', ['pending', 'capturing'])->where('expires_at', '>', now())->latest()->get();

        return view('asistencia.biometria', [
            'dispositivos' => $dispositivos,
            'usuarios' => User::with('huellas')->where('status', User::STATUS_APPROVED)->orderBy('name')->get(),
            'inscripciones' => $inscripciones,
            'dedos' => EmployeeBiometric::DEDOS,
            // La llave nueva se muestra una sola vez (viene de la sesión)
            'llaveNueva' => session('llave_nueva'),
        ]);
    }

    public function guardarDispositivo(Request $request): RedirectResponse
    {
        $data = $request->validate(RegistroDeHuellas::reglasDispositivo());

        [$d, $llave] = BiometricDevice::crear($data + ['status' => 'active']);

        return back()->with('status', "Lector «{$d->name}» creado.")
            ->with('llave_nueva', ['dispositivo' => $d->name, 'code' => $d->code, 'llave' => $llave, 'pantalla' => $d->urlPantalla()]);
    }

    public function regenerarLlave(BiometricDevice $dispositivo): RedirectResponse
    {
        $llave = $dispositivo->regenerarLlave();

        return back()->with('status', 'Llave regenerada: el agente instalado dejará de funcionar hasta que se actualice.')
            ->with('llave_nueva', ['dispositivo' => $dispositivo->name, 'code' => $dispositivo->code, 'llave' => $llave, 'pantalla' => $dispositivo->urlPantalla()]);
    }

    public function alternarDispositivo(BiometricDevice $dispositivo): RedirectResponse
    {
        $dispositivo->update(['status' => $dispositivo->status === 'active' ? 'inactive' : 'active']);

        return back()->with('status', 'Lector '.($dispositivo->status === 'active' ? 'activado' : 'desactivado').'.');
    }

    /** RH pide registrar una huella: el agente del lector elegido la recoge y guía al empleado. */
    public function solicitarRegistro(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(RegistroDeHuellas::reglasSolicitud());

        $this->huellas->solicitar($user, (int) $data['device_id'], (int) $data['finger_index'], $request->user()->id);

        return back()->with('status', "Solicitud enviada al lector. {$user->name} ya puede colocar el dedo.");
    }

    public function cancelarRegistro(BiometricEnrollment $inscripcion): RedirectResponse
    {
        $this->huellas->cancelar($inscripcion);

        return back()->with('status', 'Solicitud cancelada.');
    }

    public function eliminarHuella(EmployeeBiometric $huella): RedirectResponse
    {
        $nombre = $huella->user->name;
        $huella->delete();

        return back()->with('status', "Huella eliminada de {$nombre}.");
    }

    /** Estado de las solicitudes en curso, para que RH vea el avance sin recargar. */
    public function estadoInscripciones(): JsonResponse
    {
        $e = BiometricEnrollment::with('user:id,name')->where('updated_at', '>=', now()->subMinutes(30))->latest('updated_at')->limit(10)->get();

        return response()->json($e->map(fn ($x) => [
            'id' => $x->id, 'nombre' => $x->user->name, 'status' => $x->status, 'step' => $x->step, 'message' => $x->message,
        ]));
    }
}
