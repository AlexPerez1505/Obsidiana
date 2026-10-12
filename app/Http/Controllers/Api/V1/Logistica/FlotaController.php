<?php

namespace App\Http\Controllers\Api\V1\Logistica;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Logistica\CombustibleResource;
use App\Http\Resources\Api\V1\Logistica\FlotaVehiculoResource;
use App\Http\Resources\Api\V1\Logistica\MantenimientoResource;
use App\Http\Resources\Api\V1\Logistica\RutaResource;
use App\Models\Ruta;
use App\Models\Vehicle;
use App\Models\VehiculoCombustible;
use App\Models\VehiculoMantenimiento;
use App\Services\Logistica\ControlDeFlota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Flota: kilometraje, combustible (con rendimiento km/l) y mantenimiento
 * con avisos de lo próximo a vencer. El alta y edición de vehículos está
 * en Administración.
 *
 * @tags Logística
 */
class FlotaController extends Controller
{
    use ListadoApi;

    public function __construct(private ControlDeFlota $flota) {}

    /**
     * Tablero de flota.
     *
     * Cada vehículo con su odómetro estimado, km y gasto del mes, rendimiento
     * y avisos de mantenimiento. `con_alertas` cuenta los vehículos de toda
     * la flota que tienen algún aviso.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $pagina = Vehicle::orderBy('plate_number')->orderBy('id')->paginate($this->porPagina($request));

        return FlotaVehiculoResource::collection($pagina)->additional([
            'con_alertas' => Vehicle::all()->filter(fn (Vehicle $v) => count($this->flota->alertas($v)))->count(),
        ]);
    }

    /**
     * Ver un vehículo de la flota.
     *
     * Resumen del vehículo más sus últimas 40 cargas, últimos 40
     * mantenimientos, las 10 rutas más recientes con kilometraje y el total
     * de km recorridos en rutas. En `meta.tipos_mantenimiento` vienen los
     * tipos válidos para registrar un mantenimiento.
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'data' => (new FlotaVehiculoResource($vehicle))->resolve() + [
                'km_total' => $this->flota->kmTotal($vehicle),
                'cargas' => CombustibleResource::collection(
                    VehiculoCombustible::with('usuario')->where('vehicle_id', $vehicle->id)->latest('fecha')->latest('id')->limit(40)->get()
                ),
                'mantenimientos' => MantenimientoResource::collection(
                    VehiculoMantenimiento::where('vehicle_id', $vehicle->id)->latest('fecha')->latest('id')->limit(40)->get()
                ),
                'rutas' => RutaResource::collection(
                    Ruta::where('vehicle_id', $vehicle->id)->whereNotNull('km_recorridos')->latest('iniciada_en')->limit(10)->get()
                ),
            ],
            'meta' => ['tipos_mantenimiento' => VehiculoMantenimiento::TIPOS],
        ]);
    }

    /**
     * Cargas de combustible de un vehículo.
     *
     * Historial completo, paginado, de la más reciente a la más antigua.
     */
    public function combustibles(Request $request, Vehicle $vehicle): AnonymousResourceCollection
    {
        $cargas = VehiculoCombustible::with('usuario')->where('vehicle_id', $vehicle->id)
            ->latest('fecha')->latest('id')
            ->paginate($this->porPagina($request));

        return CombustibleResource::collection($cargas);
    }

    /**
     * Registrar carga de combustible.
     *
     * Capturada en la oficina, con su fecha. Si es de tanque lleno y trae
     * odómetro sirve para calcular el rendimiento. `ticket` opcional (foto o
     * PDF, multipart).
     */
    public function guardarCombustible(Request $request, Vehicle $vehicle): JsonResponse
    {
        $data = $request->validate($this->flota->reglasCombustible());

        $carga = VehiculoCombustible::registrar($vehicle, $data, $request->file('ticket'), $request->user()->id);

        return (new CombustibleResource($carga))
            ->additional(['message' => 'Carga de combustible registrada.'])
            ->response()->setStatusCode(201);
    }

    /**
     * Eliminar carga de combustible.
     *
     * Borra también la foto del ticket.
     */
    public function eliminarCombustible(VehiculoCombustible $carga): JsonResponse
    {
        $this->flota->eliminarCarga($carga);

        return response()->json(['message' => 'Carga eliminada.']);
    }

    /**
     * Mantenimientos de un vehículo.
     *
     * Historial completo, paginado, del más reciente al más antiguo.
     */
    public function mantenimientos(Request $request, Vehicle $vehicle): AnonymousResourceCollection
    {
        $lista = VehiculoMantenimiento::where('vehicle_id', $vehicle->id)
            ->latest('fecha')->latest('id')
            ->paginate($this->porPagina($request));

        return MantenimientoResource::collection($lista);
    }

    /**
     * Registrar mantenimiento.
     *
     * Con `proximo_fecha` o `proximo_km` la flota avisa cuando se acerca
     * (15 días o 1,000 km antes). `comprobante` opcional (foto o PDF,
     * multipart).
     */
    public function guardarMantenimiento(Request $request, Vehicle $vehicle): JsonResponse
    {
        $data = $request->validate($this->flota->reglasMantenimiento());

        $mantenimiento = $this->flota->registrarMantenimiento($vehicle, $data, $request->file('comprobante'), $request->user()->id);

        return (new MantenimientoResource($mantenimiento))
            ->additional(['message' => 'Mantenimiento registrado.'])
            ->response()->setStatusCode(201);
    }

    /**
     * Eliminar mantenimiento.
     *
     * Borra también su comprobante.
     */
    public function eliminarMantenimiento(VehiculoMantenimiento $mantenimiento): JsonResponse
    {
        $this->flota->eliminarMantenimiento($mantenimiento);

        return response()->json(['message' => 'Mantenimiento eliminado.']);
    }
}
