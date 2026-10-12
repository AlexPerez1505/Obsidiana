<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\GastoResource;
use App\Http\Resources\Api\V1\Administracion\ViajeResource;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Services\Administracion\GastosDeViaje;
use App\Services\Administracion\Viajes;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Schema;

/**
 * Viajes en curso: se inician, se les cargan gastos y se finalizan.
 * Solo administradores, como en la web.
 *
 * @tags Administración · Viáticos
 */
class ViajeController extends Controller implements HasMiddleware
{
    use ListadoApi;

    public function __construct(
        private Viajes $viajes,
        private GastosDeViaje $gastos,
    ) {
    }

    /**
     * Las tablas de viajes todavía no tienen migración: mientras no
     * existan, mejor un aviso claro que un error de SQL. Corre después de
     * la autenticación y del middleware `admin` de la ruta.
     */
    public static function middleware(): array
    {
        return [
            function (Request $request, Closure $next) {
                abort_unless(
                    Schema::hasTable('trips') && Schema::hasTable('trip_expenses'),
                    503,
                    'Los viajes todavía no están disponibles: faltan sus tablas en la base.'
                );

                return $next($request);
            },
        ];
    }

    /**
     * Listar viajes.
     *
     * Del más reciente al más viejo. Filtro opcional `status`
     * (in_progress, completed).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', 'in:in_progress,completed']]);

        $query = $this->viajes->visiblesPara($request->user())->with('user');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return ViajeResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Iniciar viaje.
     *
     * Queda en curso a nombre de quien lo inicia. Con `vehicle_id`, el
     * nombre del vehículo se toma del catálogo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(Viajes::REGLAS_INICIO);

        $trip = $this->viajes->iniciar($data, $request->user());

        return (new ViajeResource($trip->fresh()->load(['user', 'expenses'])))
            ->additional(['message' => 'Viaje iniciado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ver un viaje.
     *
     * Con sus gastos y el total.
     */
    public function show(Request $request, Trip $trip): ViajeResource
    {
        $this->autorizar($trip, $request);

        return new ViajeResource($trip->load(['user', 'expenses']));
    }

    /**
     * Agregar gasto.
     *
     * `type`: toll, fuel, meal, lodging u other. `meta` trae el total y
     * cuántos gastos lleva el viaje.
     */
    public function agregarGasto(Request $request, Trip $trip): JsonResponse
    {
        $this->autorizar($trip, $request);

        $data = $request->validate(GastosDeViaje::REGLAS);

        $gasto = $this->gastos->agregar($trip, $data);

        return (new GastoResource($gasto))
            ->additional($this->totales($trip, 'Gasto agregado.'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar gasto.
     */
    public function actualizarGasto(Request $request, Trip $trip, TripExpense $expense): GastoResource
    {
        $this->autorizarGasto($trip, $expense, $request);

        $data = $request->validate(GastosDeViaje::REGLAS);

        $this->gastos->actualizar($expense, $data);

        return (new GastoResource($expense))->additional($this->totales($trip, 'Gasto actualizado.'));
    }

    /**
     * Eliminar gasto.
     */
    public function eliminarGasto(Request $request, Trip $trip, TripExpense $expense): JsonResponse
    {
        $this->autorizarGasto($trip, $expense, $request);

        $expense->delete();

        return response()->json($this->totales($trip, 'Gasto eliminado.'));
    }

    /**
     * Finalizar viaje.
     *
     * Lo marca terminado con la hora de cierre.
     */
    public function finalizar(Request $request, Trip $trip): ViajeResource
    {
        $this->autorizar($trip, $request);

        $this->viajes->finalizar($trip);

        $trip = $trip->fresh()->load(['user', 'expenses']);

        return (new ViajeResource($trip))
            ->additional(['message' => 'Viaje finalizado. Total: $'.number_format($trip->total_computed, 2)]);
    }

    private function totales(Trip $trip, string $mensaje): array
    {
        return [
            'message' => $mensaje,
            'meta' => [
                'total' => $trip->fresh()->total_computed,
                'count' => $trip->expenses()->count(),
            ],
        ];
    }

    private function autorizar(Trip $trip, Request $request): void
    {
        abort_unless($this->viajes->puedeVer($trip, $request->user()), 403, 'Este viaje es de otra persona.');
    }

    private function autorizarGasto(Trip $trip, TripExpense $expense, Request $request): void
    {
        $this->autorizar($trip, $request);

        abort_unless(GastosDeViaje::pertenece($trip, $expense), 404, 'Ese gasto no es de este viaje.');
    }
}
