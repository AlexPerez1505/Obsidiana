<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\InscripcionResource;
use App\Models\BiometricEnrollment;
use App\Models\User;
use App\Services\Asistencia\RegistroDeHuellas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Registro de huellas: RH pide registrar un dedo de un empleado en un
 * lector; el agente de ese lector recoge la solicitud, guía al empleado
 * (3 lecturas) y la marca como terminada. Para seguir el avance, consulta
 * la inscripción cada pocos segundos.
 *
 * @tags RH · Asistencia
 */
class InscripcionController extends Controller
{
    use ListadoApi;

    public function __construct(private RegistroDeHuellas $huellas)
    {
    }

    /**
     * Listar solicitudes de registro.
     *
     * `estado=vigentes`: las que siguen en curso (pendientes o capturando y
     * sin vencer). `estado=recientes`: las que cambiaron en los últimos 30
     * minutos (lo que la web consulta para mostrar el avance). Sin
     * `estado`, todas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // vigentes | recientes
            'estado' => ['nullable', 'in:vigentes,recientes'],
            // ID del empleado (users.id).
            'empleado' => ['nullable', 'integer'],
            'dispositivo' => ['nullable', 'integer'],
        ]);

        $q = BiometricEnrollment::with(['user', 'device'])
            ->when($request->query('estado') === 'vigentes', fn ($q) => $q->whereIn('status', ['pending', 'capturing'])->where('expires_at', '>', now()))
            ->when($request->query('estado') === 'recientes', fn ($q) => $q->where('updated_at', '>=', now()->subMinutes(30)))
            ->when($request->filled('empleado'), fn ($q) => $q->where('user_id', $request->integer('empleado')))
            ->when($request->filled('dispositivo'), fn ($q) => $q->where('device_id', $request->integer('dispositivo')));

        $this->ordenar($q, $request, ['updated_at', 'created_at', 'id'], '-updated_at');

        return InscripcionResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * Ver una solicitud de registro.
     */
    public function show(BiometricEnrollment $inscripcion): InscripcionResource
    {
        return new InscripcionResource($inscripcion->load(['user', 'device']));
    }

    /**
     * Solicitar registro de huella.
     *
     * Reemplaza (cancela) las solicitudes en curso de esa persona. Vence a
     * los minutos de `asistencia.enrolamiento_minutos` (15 por defecto) si el
     * lector no la recoge.
     */
    public function store(Request $request): InscripcionResource
    {
        // user_id va en el cuerpo (en la web va en la URL); lo demás, igual que la web
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']] + RegistroDeHuellas::reglasSolicitud());

        $user = User::findOrFail($data['user_id']);
        $inscripcion = $this->huellas->solicitar($user, (int) $data['device_id'], (int) $data['finger_index'], $request->user()->id);

        /** @status 201 */
        return (new InscripcionResource($inscripcion->load(['user', 'device'])))
            ->additional(['message' => "Solicitud enviada al lector. {$user->name} ya puede colocar el dedo."]);
    }

    /**
     * Cancelar una solicitud de registro.
     *
     * Solo se cancela si sigue en curso; si ya terminó, falló o venció se
     * deja como está.
     */
    public function cancelar(BiometricEnrollment $inscripcion): InscripcionResource
    {
        $this->huellas->cancelar($inscripcion);

        return (new InscripcionResource($inscripcion->refresh()->load(['user', 'device'])))
            ->additional(['message' => 'Solicitud cancelada.']);
    }
}
