<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\ConceptoResource;
use App\Http\Resources\Api\V1\Rh\CorridaResource;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Asistencia\ServicioDeAsistencia;
use App\Services\Nomina\MotorDeNomina;
use App\Services\Nomina\QuincenaDeNomina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nómina por quincena (1–15 y 16–fin de mes). Mientras está abierta se
 * calcula en vivo con la asistencia, el sueldo de cada quien, sus
 * conceptos y las reglas activas; al cerrarla los montos se congelan.
 *
 * Todas las rutas reciben `desde`: cualquier fecha (Y-m-d) dentro de la
 * quincena; sin ella, la quincena de hoy.
 *
 * @tags RH · Nómina
 */
class QuincenaController extends Controller
{
    use ListadoApi;

    public function __construct(private QuincenaDeNomina $quincena)
    {
    }

    /**
     * Ver la quincena.
     *
     * Totales y una fila por empleado: sueldo, bonos, descuentos, neto, sus
     * métricas de asistencia, cada renglón con su explicación (`lineas`),
     * las reglas del mes que todavía están en juego (`en_juego`) y avisos.
     * `estado`: abierta | cerrada | pagada.
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            // Cualquier fecha dentro de la quincena (Y-m-d). Por defecto, hoy.
            'desde' => ['nullable', 'date'],
        ]);

        [$desde, $hasta] = $this->periodo($request);

        return response()->json(['data' => $this->datos($desde, $hasta, $this->quincena->resumen($desde, $hasta))]);
    }

    /**
     * Cerrar la quincena.
     *
     * Congela el desglose de todos. Solo se puede cuando la quincena ya
     * terminó; si todavía no termina o ya está cerrada responde 409.
     */
    public function cerrar(Request $request): JsonResponse
    {
        $request->validate([
            // Cualquier fecha dentro de la quincena (Y-m-d). Por defecto, hoy.
            'desde' => ['nullable', 'date'],
        ]);

        [$desde, $hasta] = $this->periodo($request);

        try {
            $run = $this->quincena->cerrar($desde, $hasta, $request->user()->id);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }

        return (new CorridaResource($run->load('cerro')))
            ->additional(['message' => 'Quincena cerrada: '.$run->employees.' empleados, neto $'.number_format($run->total_neto, 2).'. Los montos quedaron congelados.'])
            ->response()->setStatusCode(200);
    }

    /**
     * Reabrir la quincena.
     *
     * Descarta lo congelado: vuelve a calcularse con la asistencia y las
     * reglas actuales. Si no estaba cerrada responde 409.
     */
    public function reabrir(Request $request): JsonResponse
    {
        $request->validate([
            // Cualquier fecha dentro de la quincena (Y-m-d). Por defecto, hoy.
            'desde' => ['nullable', 'date'],
        ]);

        [$desde, $hasta] = $this->periodo($request);

        abort_unless($this->quincena->reabrir($desde, $hasta), 409, 'La quincena no está cerrada.');

        return response()->json([
            'message' => 'Quincena reabierta: vuelve a calcularse con la asistencia y las reglas actuales.',
            'data' => ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(), 'estado' => 'abierta'],
        ]);
    }

    /**
     * Marcar pagada (o pendiente de pago).
     *
     * Alterna entre pagada y cerrada. La quincena tiene que estar cerrada
     * (si no, 409).
     */
    public function pagada(Request $request): CorridaResource
    {
        $request->validate([
            // Cualquier fecha dentro de la quincena (Y-m-d). Por defecto, hoy.
            'desde' => ['nullable', 'date'],
        ]);

        [$desde, $hasta] = $this->periodo($request);

        try {
            $run = $this->quincena->alternarPagada($desde, $hasta);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }

        return (new CorridaResource($run->load('cerro')))
            ->additional(['message' => $run->status === 'pagada' ? 'Quincena marcada como pagada.' : 'Quincena marcada como pendiente de pago.']);
    }

    /**
     * Exportar la quincena (CSV).
     *
     * El mismo archivo que descarga la web: una fila por empleado con sus
     * días, faltas, retardos, sueldo, bonos, descuentos, neto y el detalle.
     * UTF-8 con BOM para que Excel lea bien los acentos.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $request->validate([
            // Cualquier fecha dentro de la quincena (Y-m-d). Por defecto, hoy.
            'desde' => ['nullable', 'date'],
        ]);

        [$desde, $hasta] = $this->periodo($request);

        return $this->quincena->descargarCsv($desde, $hasta);
    }

    /**
     * Bono o descuento solo para esta quincena.
     *
     * Se suma (o resta) a una persona únicamente en la quincena indicada.
     * Con la quincena cerrada responde 409: hay que reabrirla primero.
     */
    public function conceptoUnico(Request $request, User $user): ConceptoResource
    {
        $data = $request->validate([
            // Cualquier fecha dentro de la quincena (Y-m-d). Por defecto, hoy.
            'desde' => ['nullable', 'date'],
        ] + QuincenaDeNomina::reglasConceptoUnico(), QuincenaDeNomina::mensajesConceptoUnico());
        unset($data['desde']);

        [$desde, $hasta] = $this->periodo($request);

        try {
            $concepto = $this->quincena->agregarConceptoUnico($user, $desde, $hasta, $data, $request->user()->id);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }

        /** @status 201 */
        return (new ConceptoResource($concepto))
            ->additional(['message' => ($data['kind'] === 'bono' ? 'Bono' : 'Descuento')." agregado a {$user->name} solo para esta quincena."]);
    }

    /**
     * Historial de quincenas cerradas.
     *
     * De la más reciente a la más antigua.
     */
    public function historial(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // cerrada | pagada
            'status' => ['nullable', 'in:cerrada,pagada'],
        ]);

        $q = PayrollRun::with('cerro')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')));

        $this->ordenar($q, $request, ['period_start', 'closed_at', 'id'], '-period_start');

        return CorridaResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * La fecha se toma en hora local, igual que "hoy": así "ya terminó" y
     * "está en curso" se comparan en la misma zona.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodo(Request $request): array
    {
        $base = $request->filled('desde')
            ? Carbon::parse($request->input('desde'), ServicioDeAsistencia::tz())->startOfDay()
            : ServicioDeAsistencia::diaLocal(now());

        return MotorDeNomina::quincenaDe($base);
    }

    private function datos(Carbon $desde, Carbon $hasta, array $q): array
    {
        $run = $q['run'];
        $rango = fn (array $p) => ['desde' => $p[0]->toDateString(), 'hasta' => $p[1]->toDateString()];

        return [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'estado' => $run ? $run->status : 'abierta',
            'corrida' => $run ? new CorridaResource($run->loadMissing('cerro')) : null,
            'concluida' => $q['concluida'],
            'en_curso' => $q['en_curso'],
            'anterior' => $rango($q['anterior']),
            'siguiente' => $rango($q['siguiente']),
            'totales' => [
                'sueldos' => (float) $q['totales']['sueldos'],
                'bonos' => (float) $q['totales']['bonos'],
                'descuentos' => (float) $q['totales']['descuentos'],
                'neto' => (float) $q['totales']['neto'],
                'empleados' => (int) $q['totales']['empleados'],
            ],
            // Empleados sin sueldo capturado y días sin cerrar (pueden cambiar el cálculo)
            'sin_sueldo' => (int) $q['sin_sueldo'],
            'pendientes' => (int) $q['pendientes'],
            'filas' => $q['filas']->map(fn (array $f) => [
                'user_id' => $f['user_id'],
                'nombre' => $f['nombre'],
                'numero' => $f['numero'],
                'sueldo' => (float) $f['sueldo'],
                'bonos' => (float) $f['bonos'],
                'descuentos' => (float) $f['descuentos'],
                'neto' => (float) $f['neto'],
                'metricas' => $f['metricas'],
                'lineas' => $f['lineas'],
                'en_juego' => $f['en_juego'],
                'avisos' => $f['avisos'],
                'sin_sueldo' => $f['sin_sueldo'],
                'congelada' => $f['congelada'],
            ])->values(),
        ];
    }
}
