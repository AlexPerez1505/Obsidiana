<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\AsistenciaDiaResource;
use App\Http\Resources\Api\V1\Rh\ChecadaResource;
use App\Http\Resources\Api\V1\Rh\DiaEmpleadoResource;
use App\Http\Resources\Api\V1\Rh\EmpleadoResource;
use App\Models\AttendancePunch;
use App\Models\EmployeeAttendance;
use App\Models\TokenApi;
use App\Models\User;
use App\Services\Asistencia\ReporteDeAsistencia;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * Mi asistencia: el empleado que hace la petición ve SUS checadas, su
 * horario y el resumen de sus días. No pide ningún permiso porque solo
 * devuelve lo propio, y es de solo lectura (las correcciones las hace RH).
 * Pensado para la app móvil; una llave de integración con permisos
 * recortados no tiene acceso.
 *
 * @tags RH · Asistencia
 */
class MiAsistenciaController extends Controller
{
    use ListadoApi;

    public function __construct(private ServicioDeAsistencia $asistencia, private ReporteDeAsistencia $reporte)
    {
    }

    /**
     * Mi asistencia del mes.
     *
     * Cómo va hoy (si se me espera, mi horario del día, mi entrada y salida
     * y mis checadas) y cada día del mes pedido (por defecto el actual) con
     * su estado y sus checadas, más los totales del mes.
     */
    public function resumen(Request $request): JsonResponse
    {
        $request->validate([
            // Mes (Y-m). Por defecto, el actual.
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $user = $this->propio($request)->load('horario');
        $hoy = ServicioDeAsistencia::diaLocal(now());
        $mes = $request->filled('mes') ? Carbon::parse($request->query('mes').'-01')->startOfMonth() : $hoy->copy()->startOfMonth();
        $datos = $this->reporte->mesDeEmpleado($user, $mes);

        $fila = EmployeeAttendance::where('user_id', $user->id)->whereDate('attendance_date', $hoy)->first();
        $checadas = AttendancePunch::with('device')->validas()->where('user_id', $user->id)
            ->whereDate('work_date', $hoy)->orderBy('punched_at')->get();

        return response()->json(['data' => [
            'empleado' => new EmpleadoResource($user),
            'hoy' => [
                'fecha' => $hoy->toDateString(),
                'esperado' => $this->asistencia->esperado($user, $hoy),
                'festivo' => $this->asistencia->esFestivo($hoy),
                // {in, out, tolerance} del horario que rige hoy, o null si hoy no le toca
                'horario' => $this->asistencia->horarioDelDia($user, $hoy),
                'modalidad' => $this->asistencia->modalidadDe($user, $hoy),
                'asistencia' => $fila ? new AsistenciaDiaResource($fila) : null,
                'checadas' => ChecadaResource::collection($checadas),
            ],
            'mes' => $mes->format('Y-m'),
            'resumen' => $datos['resumen'],
            'dias' => DiaEmpleadoResource::collection($datos['dias']),
        ]]);
    }

    /**
     * Mis checadas.
     *
     * Todas mis checadas (también las anuladas, con su motivo), de la más
     * reciente a la más antigua. Filtra por rango de días con `desde`/`hasta`.
     */
    public function checadas(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            // incluir (por defecto) | excluir | solo
            'anuladas' => ['nullable', 'in:incluir,excluir,solo'],
        ]);

        $user = $this->propio($request);

        $q = AttendancePunch::with(['device', 'anuladaPor'])->where('user_id', $user->id)
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('work_date', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('work_date', '<=', $request->date('hasta')))
            ->when($request->query('anuladas') === 'excluir', fn ($q) => $q->whereNull('voided_at'))
            ->when($request->query('anuladas') === 'solo', fn ($q) => $q->whereNotNull('voided_at'));

        $this->ordenar($q, $request, ['punched_at', 'work_date', 'id'], '-punched_at');

        return ChecadaResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * El dueño del token. Una llave de integración solo puede lo que se le
     * marcó, y esto no es un permiso del catálogo: sin acceso total, no entra.
     */
    private function propio(Request $request): User
    {
        $token = $request->user()->currentAccessToken();

        abort_if(
            $token instanceof TokenApi && $token->esIntegracion() && ! $token->accesoTotal(),
            403,
            'Esta llave de integración no tiene acceso a la asistencia de su dueño.'
        );

        return $request->user();
    }
}
