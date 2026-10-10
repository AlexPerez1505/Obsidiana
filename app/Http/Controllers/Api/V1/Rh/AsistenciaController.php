<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\AsistenciaDiaResource;
use App\Http\Resources\Api\V1\Rh\ChecadaResource;
use App\Http\Resources\Api\V1\Rh\DiaEmpleadoResource;
use App\Http\Resources\Api\V1\Rh\EmpleadoResource;
use App\Models\AttendanceModality;
use App\Models\CompanyEvent;
use App\Models\EmployeeBiometric;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Services\Asistencia\ReporteDeAsistencia;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Panel de asistencia: el día, los indicadores, los empleados que checan y
 * el cierre del día. Las fechas son locales (zona de config/asistencia.php,
 * America/Mexico_City); los instantes (`punched_at`, `created_at`…) van en UTC.
 *
 * @tags RH · Asistencia
 */
class AsistenciaController extends Controller
{
    use ListadoApi;

    public function __construct(private ServicioDeAsistencia $asistencia, private ReporteDeAsistencia $reporte)
    {
    }

    /**
     * Asistencia del día.
     *
     * Lo mismo que el panel de la web: cuántos se esperaban, presentes,
     * retardos, faltas, incidencias, y el estado de cada empleado que checa
     * (`puntual`, `retardo`, `falta`, `sin_checar` o el tipo de incidencia que
     * lo justifica). Sin `fecha` es hoy. Igual que la web, antes cierra los
     * días de la última semana que el cron no haya cerrado.
     */
    public function dia(Request $request): JsonResponse
    {
        $request->validate([
            // Día a consultar (Y-m-d). Por defecto, hoy.
            'fecha' => ['nullable', 'date'],
        ]);

        // Respaldo por si el cron no corrió, como en la web
        $this->asistencia->cerrarPendientes();

        $dia = $request->filled('fecha') ? Carbon::parse($request->query('fecha'))->startOfDay() : ServicioDeAsistencia::diaLocal(now());
        $datos = $this->reporte->dia($dia);

        return response()->json(['data' => [
            'fecha' => $dia->toDateString(),
            'es_hoy' => $dia->toDateString() === ServicioDeAsistencia::diaLocal(now())->toDateString(),
            'resumen' => $datos['resumen'],
            'empleados' => collect($datos['empleados'])->map(fn (array $e) => [
                'empleado' => new EmpleadoResource($e['user']),
                'estado' => $e['estado'],
                'modalidad' => $e['modalidad'],
                'asistencia' => $e['fila'] ? new AsistenciaDiaResource($e['fila']) : null,
            ])->values(),
            // Las últimas 12 checadas de hoy, sin importar la fecha consultada
            'ultimas_checadas' => ChecadaResource::collection($this->reporte->ultimasChecadas()),
            'sin_horario' => User::where('status', User::STATUS_APPROVED)->whereDoesntHave('horario')->count(),
        ]]);
    }

    /**
     * Indicadores de asistencia.
     *
     * Puntualidad, hora promedio de llegada, quién llega más temprano, quién
     * acumula retardos y faltas, y la asistencia por puesto del mes pedido;
     * además la serie de los últimos 6 meses y la hora promedio de entrada
     * de los últimos 14 días (como las gráficas del panel).
     */
    public function indicadores(Request $request): JsonResponse
    {
        $request->validate([
            // Mes (Y-m). Por defecto, el actual.
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $mes = $this->mes($request);

        return response()->json(['data' => [
            'mes' => $mes->format('Y-m'),
            'kpis' => $this->reporte->mes($mes),
            'serie' => $this->reporte->serieMensual(6),
            'hora_promedio' => $this->reporte->horaPromedioPorDia(14),
            'por_puesto' => $this->reporte->porPuesto($mes),
        ]]);
    }

    /**
     * Catálogos de asistencia.
     *
     * Etiquetas para armar formularios: días de la semana (1 = lunes),
     * horario base, tipos de evento, tipos de incidencia y dedos (0-9).
     */
    public function catalogos(): JsonResponse
    {
        return response()->json(['data' => [
            'zona_horaria' => ServicioDeAsistencia::tz(),
            'dias' => EmployeeSchedule::DIAS,
            'horario_base' => config('asistencia.horario_base'),
            'tipos_evento' => CompanyEvent::TIPOS,
            'tipos_incidencia' => AttendanceModality::TIPOS,
            'dedos' => EmployeeBiometric::DEDOS,
        ]]);
    }

    /**
     * Empleados que checan.
     *
     * Cuentas aprobadas con horario activo: las que el sistema espera cada
     * día laboral. Útil para los filtros por empleado.
     */
    public function empleados(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // Busca en nombre, correo o número de checador.
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $q = $this->asistencia->consultaEmpleados()
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = '%'.$request->query('buscar').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $b)->orWhere('email', 'like', $b)->orWhere('checador_id', 'like', $b));
            });

        $this->ordenar($q, $request, ['name', 'id'], 'name');

        return EmpleadoResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * Asistencia de un empleado en el mes.
     *
     * Cada día del mes con lo que se esperaba, si fue festivo, qué lo
     * justifica, su resumen y sus checadas (también las anuladas), más los
     * totales del mes, su horario y sus huellas registradas (sin plantillas).
     */
    public function empleado(Request $request, User $user): JsonResponse
    {
        $request->validate([
            // Mes (Y-m). Por defecto, el actual.
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $mes = $this->mes($request);
        $datos = $this->reporte->mesDeEmpleado($user, $mes);

        return response()->json(['data' => [
            'empleado' => new EmpleadoResource($user->load(['horario', 'huellas'])),
            'mes' => $mes->format('Y-m'),
            'resumen' => $datos['resumen'],
            'dias' => DiaEmpleadoResource::collection($datos['dias']),
        ]]);
    }

    /**
     * Cerrar un día.
     *
     * Genera las faltas automáticas de un día ya terminado (quien debía
     * checar y no tiene checada ni justificante) o lo recalcula completo si
     * ya estaba cerrado. Devuelve cuántas faltas y justificadas quedaron.
     */
    public function cerrarDia(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Día a cerrar (Y-m-d), anterior a hoy.
            'fecha' => ['required', 'date', 'before:today'],
        ]);

        // "before:today" compara con la fecha UTC; en la tarde-noche de México
        // eso ya es "mañana" y dejaría cerrar el día en curso.
        $dia = Carbon::parse($data['fecha'])->startOfDay();

        if ($dia->toDateString() >= ServicioDeAsistencia::diaLocal(now())->toDateString()) {
            throw ValidationException::withMessages(['fecha' => 'Ese día todavía no termina: solo se cierran días pasados.']);
        }

        $r = $this->asistencia->cerrarDia($dia);

        return response()->json([
            'message' => "Día cerrado: {$r['faltas']} falta(s), {$r['justificadas']} justificada(s), {$r['revisados']} empleado(s) revisados.",
            'data' => ['fecha' => $dia->toDateString()] + $r,
        ]);
    }

    private function mes(Request $request): Carbon
    {
        return $request->filled('mes')
            ? Carbon::parse($request->query('mes').'-01')->startOfMonth()
            : ServicioDeAsistencia::diaLocal(now())->startOfMonth();
    }
}
