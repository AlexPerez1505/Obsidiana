<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\EmployeeBiometric;
use App\Models\User;
use App\Services\Asistencia\EventosDelChecador;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API del agente instalado junto al lector de huella.
 *
 * El agente identifica la huella (con el SDK del lector) y avisa aquí; Laravel
 * decide qué significa: entrada, salida, retardo… El agente nunca decide eso.
 */
class ChecadorApiController extends Controller
{
    public function __construct(private ServicioDeAsistencia $asistencia)
    {
    }

    private function dispositivo(Request $r): BiometricDevice
    {
        return $r->attributes->get('dispositivo');
    }

    /** Señal de vida: devuelve la hora del servidor y si hay algo pendiente. */
    public function ping(Request $request): JsonResponse
    {
        $d = $this->dispositivo($request);

        return response()->json([
            'ok' => true,
            'device' => ['code' => $d->code, 'name' => $d->name],
            'server_time' => now()->toIso8601String(),
            'timezone' => ServicioDeAsistencia::tz(),
            'pending_enrollment' => BiometricEnrollment::where('device_id', $d->id)
                ->whereIn('status', ['pending', 'capturing'])->where('expires_at', '>', now())->exists(),
            'templates_version' => $this->versionPlantillas(),
        ]);
    }

    /**
     * Plantillas activas para identificar localmente (1:N). Solo viajan por
     * HTTPS hacia un dispositivo autenticado; en la base están cifradas.
     */
    public function plantillas(Request $request): JsonResponse
    {
        $filas = EmployeeBiometric::with('user:id,name,checador_id,status')
            ->where('status', 'active')
            ->whereHas('user', fn ($q) => $q->where('status', User::STATUS_APPROVED))
            ->get();

        return response()->json([
            'ok' => true,
            'version' => $this->versionPlantillas(),
            'templates' => $filas->map(fn (EmployeeBiometric $b) => [
                'employee_id' => $b->user_id,
                'employee_number' => $b->user->checador_id,
                'name' => $b->user->name,
                'finger_index' => $b->finger_index,
                'format' => $b->template_format,
                'template' => $b->plantilla(),
            ])->values(),
        ]);
    }

    /**
     * Una huella identificada. Acepta employee_id (id interno) o
     * employee_number (el checador_id del perfil de RH). Reintentar con el
     * mismo uuid es seguro.
     */
    public function checar(Request $request): JsonResponse
    {
        $d = $this->dispositivo($request);

        $data = $request->validate([
            'employee_id' => ['nullable', 'integer', 'required_without:employee_number'],
            'employee_number' => ['nullable', 'string', 'max:100', 'required_without:employee_id'],
            'uuid' => ['nullable', 'uuid'],
            'punched_at' => ['nullable', 'date'],
            'confidence' => ['nullable', 'integer', 'between:0,100'],
        ]);

        $user = ! empty($data['employee_id'])
            ? User::find($data['employee_id'])
            : User::where('checador_id', $data['employee_number'])->first();

        if (! $user || ! $user->isApproved()) {
            EventosDelChecador::publicar($d->id, 'no_reconocido');

            return response()->json(['ok' => false, 'code' => 'unknown_employee', 'message' => 'Empleado no reconocido.'], 404);
        }

        $res = $this->asistencia->registrarChecada($user, $d, [
            'uuid' => $data['uuid'] ?? null,
            'punched_at' => $data['punched_at'] ?? null,
            'confidence' => $data['confidence'] ?? null,
        ]);

        // Una checada duplicada (dedo repetido) no vuelve a mostrar el saludo
        if (! $res['duplicada']) {
            EventosDelChecador::publicar($d->id, 'checada', $res['resultado']);
        } else {
            EventosDelChecador::publicar($d->id, 'repetida', ['employee' => $res['resultado']['employee']]);
        }

        return response()->json(['ok' => true] + $res['resultado']);
    }

    /** Huella que el lector no pudo identificar: solo para mostrarlo en pantalla. */
    public function noReconocida(Request $request): JsonResponse
    {
        EventosDelChecador::publicar($this->dispositivo($request)->id, 'no_reconocido');

        return response()->json(['ok' => true]);
    }

    // ---------------- Registro de huellas ----------------

    /** ¿RH pidió registrar una huella en este lector? Se toma la más antigua vigente. */
    public function inscripcionPendiente(Request $request): JsonResponse
    {
        $d = $this->dispositivo($request);

        BiometricEnrollment::where('device_id', $d->id)->whereIn('status', ['pending', 'capturing'])
            ->where('expires_at', '<=', now())->update(['status' => 'failed', 'message' => 'La solicitud expiró.']);

        $e = BiometricEnrollment::with('user:id,name,checador_id')->where('device_id', $d->id)
            ->whereIn('status', ['pending', 'capturing'])->orderBy('id')->first();

        if (! $e) {
            return response()->json(['ok' => true, 'enrollment' => null]);
        }

        if ($e->status === 'pending') {
            $e->update(['status' => 'capturing', 'step' => 0, 'message' => 'Coloca el dedo en el lector']);
            EventosDelChecador::publicar($d->id, 'registro', ['employee' => ['name' => $e->user->name], 'step' => 0, 'message' => $e->message]);
        }

        return response()->json(['ok' => true, 'enrollment' => [
            'id' => $e->id,
            'employee_id' => $e->user_id,
            'employee_number' => $e->user->checador_id,
            'name' => $e->user->name,
            'finger_index' => $e->finger_index,
            'readings_required' => 3,
        ]]);
    }

    /** Avance de las lecturas (1, 2, 3) para que RH y la pantalla lo vean. */
    public function inscripcionAvance(Request $request, BiometricEnrollment $inscripcion): JsonResponse
    {
        $d = $this->dispositivo($request);
        abort_unless($inscripcion->device_id === $d->id, 403);

        $data = $request->validate(['step' => ['required', 'integer', 'between:0,3'], 'message' => ['nullable', 'string', 'max:255']]);

        $texto = $data['message'] ?? match ((int) $data['step']) {
            0 => 'Coloca el dedo en el lector',
            1 => 'Lectura 1 lista: retira y coloca de nuevo',
            2 => 'Lectura 2 lista: una última vez',
            default => 'Lectura 3 lista',
        };

        $inscripcion->update(['step' => $data['step'], 'message' => $texto]);
        EventosDelChecador::publicar($d->id, 'registro', ['employee' => ['name' => $inscripcion->user->name], 'step' => (int) $data['step'], 'message' => $texto]);

        return response()->json(['ok' => true]);
    }

    /** El agente sube la plantilla ya generada con las 3 lecturas. */
    public function inscripcionCompletar(Request $request, BiometricEnrollment $inscripcion): JsonResponse
    {
        $d = $this->dispositivo($request);
        abort_unless($inscripcion->device_id === $d->id, 403);

        if (! $inscripcion->vigente()) {
            return response()->json(['ok' => false, 'message' => 'La solicitud ya no está vigente.'], 409);
        }

        $data = $request->validate([
            'template' => ['required', 'string', 'max:500000'],
            'template_format' => ['nullable', 'string', 'max:30'],
        ]);

        // El mismo dedo no se duplica: se reemplaza
        EmployeeBiometric::updateOrCreate(
            ['user_id' => $inscripcion->user_id, 'finger_index' => $inscripcion->finger_index],
            [
                'template' => $data['template'],
                'template_format' => $data['template_format'] ?? 'generic',
                'device_id' => $d->id,
                'status' => 'active',
                'registered_at' => now(),
                'registered_by' => $inscripcion->requested_by,
            ]
        );

        $inscripcion->update(['status' => 'done', 'step' => 3, 'message' => 'Huella registrada']);
        EventosDelChecador::publicar($d->id, 'registro_listo', ['employee' => ['name' => $inscripcion->user->name]]);

        return response()->json(['ok' => true, 'templates_version' => $this->versionPlantillas()]);
    }

    public function inscripcionFallar(Request $request, BiometricEnrollment $inscripcion): JsonResponse
    {
        $d = $this->dispositivo($request);
        abort_unless($inscripcion->device_id === $d->id, 403);

        $data = $request->validate(['message' => ['nullable', 'string', 'max:255']]);
        $inscripcion->update(['status' => 'failed', 'message' => $data['message'] ?? 'No se pudo completar el registro.']);
        EventosDelChecador::publicar($d->id, 'registro_fallido', ['message' => $inscripcion->message]);

        return response()->json(['ok' => true]);
    }

    /** Cambia cuando se agrega, reemplaza o borra una huella: el agente sabe cuándo re-descargar. */
    private function versionPlantillas(): string
    {
        $max = EmployeeBiometric::max('updated_at');

        return substr(sha1(($max ?? '0').'|'.EmployeeBiometric::where('status', 'active')->count()), 0, 16);
    }
}
