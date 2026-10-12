<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\EmpleadoResource;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Services\Asistencia\ConfiguracionDeAsistencia;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Horarios por empleado: qué días trabaja, a qué hora entra y sale (puede
 * variar por día, ej. sábados de 9 a 14) y su tolerancia de retardo.
 *
 * El horario se manda por día de la semana (1 = lunes … 7 = domingo):
 *
 *     "days": { "1": {"on": true, "in": "09:00", "out": "18:00"},
 *               "6": {"on": true, "in": "09:00", "out": "14:00"} }
 *
 * Los días sin `on` son descanso. Es la misma forma que devuelve `horario.days`.
 *
 * @tags RH · Asistencia
 */
class HorarioController extends Controller
{
    use ListadoApi;

    public function __construct(private ConfiguracionDeAsistencia $config)
    {
    }

    /**
     * Listar horarios.
     *
     * Las cuentas aprobadas con su horario (o `null` si aún no tienen) y
     * cuántas huellas tienen registradas. Trae también el horario base y los
     * nombres de los días.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // Busca en nombre, correo o número de checador.
            'buscar' => ['nullable', 'string', 'max:100'],
            // true: solo con horario; false: solo sin horario.
            'con_horario' => ['nullable', 'boolean'],
        ]);

        $q = User::with('horario')->withCount('huellas')->where('status', User::STATUS_APPROVED)
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = '%'.$request->query('buscar').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $b)->orWhere('email', 'like', $b)->orWhere('checador_id', 'like', $b));
            })
            ->when($request->filled('con_horario'), fn ($q) => $request->boolean('con_horario') ? $q->whereHas('horario') : $q->whereDoesntHave('horario'));

        $this->ordenar($q, $request, ['name', 'id'], 'name');

        return EmpleadoResource::collection($q->paginate($this->porPagina($request)))
            ->additional(['horario_base' => config('asistencia.horario_base'), 'dias' => EmployeeSchedule::DIAS]);
    }

    /**
     * Ver el horario de un empleado.
     */
    public function show(User $user): EmpleadoResource
    {
        return new EmpleadoResource($user->load('horario')->loadCount('huellas'));
    }

    /**
     * Guardar el horario de un empleado.
     *
     * Crea o reemplaza su horario. Si viene `checador_id` también se guarda
     * (vacío lo quita). Cambiar el horario no recalcula los días pasados.
     *
     * @throws ValidationException
     */
    #[BodyParameter('days', 'Horario por día de la semana (1 = lunes … 7 = domingo): {"1": {"on": true, "in": "09:00", "out": "18:00"}}.', required: true, type: 'object')]
    #[BodyParameter('tolerance_minutes', 'Minutos de tolerancia antes de contar retardo (0-60).', required: true, type: 'integer')]
    #[BodyParameter('active', 'Si el horario está activo (sin horario activo no se le esperan checadas).', type: 'boolean')]
    #[BodyParameter('checador_id', 'Número de checador del empleado (único).', type: 'string')]
    public function update(Request $request, User $user): EmpleadoResource
    {
        $this->config->guardarHorario($user, $request->all());

        return (new EmpleadoResource($user->refresh()->load('horario')->loadCount('huellas')))
            ->additional(['message' => "Horario de {$user->name} guardado."]);
    }

    /**
     * Aplicar el horario base.
     *
     * Da de alta el horario base (config/asistencia.php) a todas las cuentas
     * aprobadas que todavía no tienen horario. No toca a quien ya tiene uno.
     */
    public function base(): JsonResponse
    {
        $n = $this->config->aplicarBase();

        return response()->json([
            'message' => $n ? "Horario base aplicado a {$n} persona(s)." : 'Todos ya tienen horario.',
            'data' => ['aplicados' => $n],
        ]);
    }

    /**
     * Aplicar un horario a varias personas.
     *
     * El mismo horario (días, horas y tolerancia) para todas las cuentas
     * aprobadas (`alcance: todos`) o solo para las de `user_ids`
     * (`alcance: seleccion`). Reemplaza el horario que tuvieran.
     *
     * @throws ValidationException
     */
    #[BodyParameter('alcance', 'todos | seleccion', required: true, type: 'string')]
    #[BodyParameter('user_ids', 'IDs de las personas, si el alcance es "seleccion".', type: 'array')]
    #[BodyParameter('days', 'Horario por día de la semana, igual que al guardar uno.', required: true, type: 'object')]
    #[BodyParameter('tolerance_minutes', 'Minutos de tolerancia (0-60).', required: true, type: 'integer')]
    #[BodyParameter('active', 'Si el horario queda activo.', type: 'boolean')]
    public function masivo(Request $request): JsonResponse
    {
        $n = $this->config->aplicarHorarioMasivo($request->all());

        return response()->json([
            'message' => "Horario aplicado a {$n} persona(s).",
            'data' => ['aplicados' => $n],
        ]);
    }
}
