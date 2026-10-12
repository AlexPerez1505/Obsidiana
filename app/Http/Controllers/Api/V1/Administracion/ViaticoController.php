<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\GastoResource;
use App\Http\Resources\Api\V1\Administracion\ViaticoResource;
use App\Models\Viatic;
use App\Models\ViaticExpense;
use App\Services\Administracion\GastosDeViaje;
use App\Services\Administracion\Viaticos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Viáticos: casetas, gasolina, comidas y hospedaje de una salida, con las
 * fotos de sus tickets. Solo administradores, como en la web.
 *
 * Para mandar fotos al editar, usa POST multipart con `_method=PATCH`
 * (PHP no lee archivos en un PATCH real).
 *
 * @tags Administración · Viáticos
 */
class ViaticoController extends Controller
{
    use ListadoApi;

    public function __construct(
        private Viaticos $viaticos,
        private GastosDeViaje $gastos,
    ) {
    }

    /**
     * Listar viáticos.
     *
     * Del más reciente al más viejo. Filtros: `buscar` (lugar, vehículo o
     * nombre de quien lo registró) y `status` (pending, approved,
     * rejected).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
        ]);

        $query = $this->viaticos->visiblesPara($request->user(), $request->query('buscar'), $request->query('status'));

        return ViaticoResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Ver un viático.
     *
     * Con sus gastos sueltos y las fotos del ticket.
     */
    public function show(Request $request, Viatic $viatic): ViaticoResource
    {
        $this->autorizar($viatic, $request);

        return new ViaticoResource($viatic->load(['user', 'expenses']));
    }

    /**
     * Registrar viático.
     *
     * Multipart si lleva fotos (`ticket_photos[]`, imágenes de hasta 5 MB).
     * Cada total con monto (`tolls`, `fuel`, `meals`, `lodging`,
     * `additional`) se vuelve también un gasto suelto. Con `vehicle_id`, el
     * nombre del vehículo se toma del catálogo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(Viaticos::REGLAS);

        $viatic = $this->viaticos->registrar($data, $request->user(), $request->file('ticket_photos', []));

        return (new ViaticoResource($viatic->fresh()->load(['user', 'expenses'])))
            ->additional(['message' => 'Viático registrado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar viático.
     *
     * Mismos campos que el alta. `ticket_photos[]` agrega fotos a las que
     * ya hay; `quitar_fotos[]` (rutas de `ticket_photos`) quita las
     * indicadas y borra su archivo.
     */
    public function update(Request $request, Viatic $viatic): ViaticoResource
    {
        $this->autorizar($viatic, $request);

        $data = $request->validate(Viaticos::REGLAS + Viaticos::REGLAS_QUITAR_FOTOS);

        $this->viaticos->actualizar($viatic, $data, $request->file('ticket_photos', []));

        return (new ViaticoResource($viatic->fresh()->load(['user', 'expenses'])))
            ->additional(['message' => 'Viático actualizado correctamente.']);
    }

    /**
     * Eliminar viático.
     *
     * Borra también sus gastos y las fotos del ticket.
     */
    public function destroy(Request $request, Viatic $viatic): JsonResponse
    {
        $this->autorizar($viatic, $request);

        $this->viaticos->eliminar($viatic);

        return response()->json(['message' => 'Viático eliminado correctamente.']);
    }

    /**
     * Agregar gasto.
     *
     * `type`: toll, fuel, meal, lodging u other. `meta` trae el total y
     * cuántos gastos lleva el viático.
     */
    public function agregarGasto(Request $request, Viatic $viatic): JsonResponse
    {
        $this->autorizar($viatic, $request);

        $data = $request->validate(GastosDeViaje::REGLAS);

        $gasto = $this->gastos->agregar($viatic, $data);

        return (new GastoResource($gasto))
            ->additional($this->totales($viatic, 'Gasto agregado.'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar gasto.
     */
    public function actualizarGasto(Request $request, Viatic $viatic, ViaticExpense $expense): GastoResource
    {
        $this->autorizarGasto($viatic, $expense, $request);

        $data = $request->validate(GastosDeViaje::REGLAS);

        $this->gastos->actualizar($expense, $data);

        return (new GastoResource($expense))->additional($this->totales($viatic, 'Gasto actualizado.'));
    }

    /**
     * Eliminar gasto.
     */
    public function eliminarGasto(Request $request, Viatic $viatic, ViaticExpense $expense): JsonResponse
    {
        $this->autorizarGasto($viatic, $expense, $request);

        $expense->delete();

        return response()->json($this->totales($viatic, 'Gasto eliminado.'));
    }

    private function totales(Viatic $viatic, string $mensaje): array
    {
        return [
            'message' => $mensaje,
            'meta' => [
                'total' => $viatic->fresh()->total_computed,
                'count' => $viatic->expenses()->count(),
            ],
        ];
    }

    private function autorizar(Viatic $viatic, Request $request): void
    {
        abort_unless($this->viaticos->puedeVer($viatic, $request->user()), 403, 'Este viático es de otra persona.');
    }

    private function autorizarGasto(Viatic $viatic, ViaticExpense $expense, Request $request): void
    {
        $this->autorizar($viatic, $request);

        abort_unless(GastosDeViaje::pertenece($viatic, $expense), 404, 'Ese gasto no es de este viático.');
    }
}
