<?php

namespace App\Http\Controllers\Api\V1\Servicios;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Servicios\ServicioResource;
use App\Models\Service;
use App\Services\Servicios\Mantenimientos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Servicios
 */
class MantenimientoController extends Controller
{
    use ListadoApi;

    public function __construct(private Mantenimientos $mantenimientos)
    {
    }

    /**
     * Técnicos de mantenimiento.
     *
     * Los técnicos internos con cuántos servicios traen en progreso, más
     * una opción con `id: "externo"` que agrupa todos los mantenimientos
     * externos. Sirve para armar las pestañas del tablero.
     */
    public function tecnicos(): JsonResponse
    {
        return response()->json(['data' => $this->mantenimientos->tecnicos()]);
    }

    /**
     * Tablero de mantenimiento.
     *
     * Los servicios de un técnico interno (`tecnico=<id>`: los que trae en
     * progreso) o todos los externos (`tipo=externo`). Sin ninguno de los
     * dos, el primer técnico de la lista. En `seleccionado` viene a quién
     * corresponde el listado.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'tipo' => ['nullable', 'in:externo'],
            'tecnico' => ['nullable', 'integer'],
        ]);

        $tecnicos = $this->mantenimientos->tecnicos();
        $seleccionado = $this->mantenimientos->seleccionar($tecnicos, $request->query('tipo'), $request->query('tecnico'));

        $servicios = $this->mantenimientos->servicios($seleccionado)
            ->orderByDesc('id')
            ->paginate($this->porPagina($request));

        return ServicioResource::collection($servicios)->additional(['seleccionado' => $seleccionado]);
    }

    /**
     * Ver la hoja de mantenimiento.
     *
     * `datos` es lo que la hoja trae prellenado de la orden (cliente,
     * folio, equipo, marca, modelo, serie, diagnóstico, fecha y técnico).
     * `reporte` es la hoja tal como se guardó la última vez (el mismo JSON
     * que arma el editor de la web), o null si nunca se ha guardado.
     */
    public function reporte(Service $service): JsonResponse
    {
        return response()->json(['data' => $this->hoja($service)]);
    }

    /**
     * Guardar la hoja de mantenimiento.
     *
     * Reemplaza la hoja guardada con `reporte` (un objeto JSON con lo
     * capturado). Mandar `reporte: null` la borra.
     */
    public function guardarReporte(Request $request, Service $service): JsonResponse
    {
        $request->validate([
            'reporte' => ['present', 'nullable', 'array'],
        ]);

        $this->mantenimientos->guardarReporte($service, $request->input('reporte'));

        return response()->json(['message' => 'Reporte guardado.', 'data' => $this->hoja($service)]);
    }

    /** @return array{service_id: int, service_number: string|null, datos: array<string, string>, reporte: array|null, updated_at: mixed} */
    private function hoja(Service $service): array
    {
        return [
            'service_id' => $service->id,
            'service_number' => $service->service_number,
            'datos' => $this->mantenimientos->datosDelReporte($service),
            'reporte' => $this->mantenimientos->reporteGuardado($service),
            'updated_at' => $service->updated_at,
        ];
    }
}
