<?php

namespace App\Http\Controllers\Api\V1\Logistica;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Api\V1\Logistica\Concerns\ReglasDeRuta;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Logistica\RutaEnVivoResource;
use App\Http\Resources\Api\V1\Logistica\RutaResource;
use App\Http\Resources\Api\V1\Logistica\VehiculoResource;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\OrdenSalida;
use App\Models\Ruta;
use App\Models\RutaParada;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Logistica\Geocodificador;
use App\Services\Logistica\OperacionDeRutas;
use App\Services\Logistica\PlaneacionDeRutas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Rutas de reparto desde la oficina: alta con paradas, edición antes de
 * salir, cálculo del mejor orden y control de la salida y el cierre. Lo
 * que hace el chofer en el camino está en "Logística · chofer".
 *
 * @tags Logística
 */
class RutaController extends Controller
{
    use ListadoApi, ReglasDeRuta;

    public function __construct(private PlaneacionDeRutas $planeacion) {}

    /**
     * Listar rutas.
     *
     * Por defecto solo las abiertas (borrador, programada y en ruta), de la
     * fecha más reciente a la más antigua. `estado` acepta `abiertas`,
     * `todas` o un estado puntual; `q` busca en el folio y en el nombre de
     * las paradas. Junto a la página viene `conteos` con cuántas hay por
     * estado, como las pestañas de la web.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $f = $request->validate([
            'estado' => ['nullable', Rule::in(['abiertas', 'todas', ...array_keys(Ruta::ESTADOS)])],
            'q' => ['nullable', 'string', 'max:100'],
            'chofer_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
            // Rango de fecha programada
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $estado = $f['estado'] ?? 'abiertas';

        $query = Ruta::with(['vehicle', 'chofer', 'paradas'])
            ->when($estado === 'abiertas', fn ($q) => $q->whereNotIn('estado', [Ruta::FINALIZADA, Ruta::CANCELADA]))
            ->when(! in_array($estado, ['abiertas', 'todas'], true), fn ($q) => $q->where('estado', $estado))
            ->when($f['q'] ?? null, function ($q, $texto) {
                $t = '%'.$texto.'%';
                $q->where(fn ($w) => $w->where('folio', 'like', $t)
                    ->orWhereHas('paradas', fn ($p) => $p->where('nombre', 'like', $t)));
            })
            ->when($f['chofer_id'] ?? null, fn ($q, $id) => $q->where('chofer_id', $id))
            ->when($f['vehicle_id'] ?? null, fn ($q, $id) => $q->where('vehicle_id', $id))
            ->when($f['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha_programada', '>=', $d))
            ->when($f['hasta'] ?? null, fn ($q, $d) => $q->whereDate('fecha_programada', '<=', $d));

        $this->ordenar($query, $request, ['fecha_programada', 'id', 'folio', 'created_at', 'iniciada_en', 'finalizada_en'], '-fecha_programada')
            ->orderByDesc('id');

        $conteos = Ruta::selectRaw('estado, COUNT(*) as n')->groupBy('estado')->pluck('n', 'estado');

        return RutaResource::collection($query->paginate($this->porPagina($request)))->additional([
            'conteos' => [
                'abiertas' => (int) ($conteos[Ruta::BORRADOR] ?? 0) + (int) ($conteos[Ruta::PROGRAMADA] ?? 0) + (int) ($conteos[Ruta::EN_RUTA] ?? 0),
            ] + collect(Ruta::ESTADOS)->map(fn ($l, $e) => (int) ($conteos[$e] ?? 0))->all(),
        ]);
    }

    /**
     * Catálogos para armar una ruta.
     *
     * Lo que necesita el formulario de alta o edición: vehículos activos,
     * usuarios que pueden ir de chofer, órdenes de salida abiertas que se
     * pueden ligar a una parada, el punto de salida por defecto y si hay
     * Google Maps configurado (si no, el orden se calcula en local). Pide
     * permiso de crear o de editar rutas.
     */
    public function catalogos(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['rutas.crear', 'rutas.editar']), 403);

        return response()->json(['data' => [
            'vehiculos' => VehiculoResource::collection(Vehicle::where('status', Vehicle::STATUS_ACTIVE)->orderBy('plate_number')->get()),
            'choferes' => UsuarioResource::collection(User::orderBy('name')->get()->filter(fn (User $u) => $u->isApproved())->values()),
            'ordenes' => $this->planeacion->ordenesDisponibles(),
            'origen' => config('logistica.origen'),
            'google_activo' => (bool) config('logistica.google_key'),
            'estados' => Ruta::ESTADOS,
            'estados_parada' => RutaParada::ESTADOS,
        ]]);
    }

    /**
     * Órdenes listas para ruta.
     *
     * Las órdenes de salida listas para firma que todavía no van en una
     * ruta abierta (máx. 30, las más antiguas primero). Cada una trae
     * `parada` ya armada: mándalas tal cual en `paradas` al crear la ruta
     * (es el "Armar desde órdenes listas" de la web). Al cerrar la entrega
     * en ruta, la orden se marca entregada con la firma del receptor.
     */
    public function ordenesListas(): JsonResponse
    {
        $ordenes = $this->planeacion->ordenesListas()->map(fn (OrdenSalida $o) => [
            'orden_salida_id' => $o->id,
            'folio' => $o->folio,
            'venta_id' => $o->venta_id,
            'parada' => $this->planeacion->paradaDesdeOrden($o),
        ])->values();

        return response()->json(['data' => $ordenes]);
    }

    /**
     * Buscar una dirección.
     *
     * Convierte texto en coordenadas para ubicar una parada (Google si hay
     * llave; si no, OpenStreetMap). Hasta 5 resultados; con menos de 4
     * letras regresa vacío.
     */
    public function geocodificar(Request $request, Geocodificador $geo): JsonResponse
    {
        $d = $request->validate(['q' => ['required', 'string', 'max:255']]);

        return response()->json(['data' => $geo->buscar($d['q'])]);
    }

    /**
     * Crear ruta.
     *
     * Con vehículo y chofer queda `programada`; si falta alguno, en
     * `borrador`. `mercancia` de cada parada es texto con un renglón por
     * artículo ("2 Monitor Philips", "Ventilador x3"); si se deja vacío y la
     * parada lleva `orden_salida_id`, se toma de la orden. Después
     * optimízala para calcular el orden y las ETA.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->planeacion->reglas(), $this->planeacion->mensajes());
        $this->sinChoque($data);

        $ruta = $this->planeacion->crear($data, $request->user()->id);
        $ruta->load(['vehicle', 'chofer', 'paradas.items']);

        return (new RutaResource($ruta))
            ->additional(['message' => "Ruta {$ruta->folio} creada. Optimízala para definir el mejor orden."])
            ->response()->setStatusCode(201);
    }

    /**
     * Ver una ruta.
     *
     * Todo lo de la pantalla de detalle: paradas con mercancía, evidencias
     * y orden de salida ligada, bitácora de eventos, desviaciones,
     * incidencias, última posición y los trazos planeado y real.
     */
    public function show(Ruta $ruta): RutaResource
    {
        $ruta->load(['vehicle', 'chofer', 'creador', 'paradas.items', 'paradas.evidencias', 'paradas.ordenSalida', 'ultimaUbicacion', 'eventos.usuario', 'desviaciones', 'incidencias.usuario']);

        return (new RutaResource($ruta))->conMapa();
    }

    /**
     * Estado en vivo de una ruta.
     *
     * Versión ligera para consultar cada pocos segundos mientras la ruta va
     * en curso: posición del vehículo, siguiente parada y el estado de cada
     * parada.
     */
    public function enVivo(Ruta $ruta): RutaEnVivoResource
    {
        $ruta->load(['paradas', 'ultimaUbicacion']);

        return new RutaEnVivoResource($ruta);
    }

    /**
     * Editar ruta.
     *
     * Solo en borrador o programada. Reemplaza los datos y TODAS las
     * paradas (manda la lista completa) y borra el cálculo anterior: hay que
     * volver a optimizarla.
     */
    public function update(Request $request, Ruta $ruta): RutaResource
    {
        abort_unless($ruta->editable(), 409, 'Una ruta en curso o cerrada ya no se edita.');

        $data = $request->validate($this->planeacion->reglas(), $this->planeacion->mensajes());
        $this->sinChoque($data, $ruta);

        $this->operar(fn () => $this->planeacion->actualizar($ruta, $data, $request->user()->id));

        return (new RutaResource($ruta->fresh(['vehicle', 'chofer', 'paradas.items'])))
            ->additional(['message' => 'Ruta actualizada.']);
    }

    /**
     * Eliminar ruta.
     *
     * No se puede mientras va en curso: primero finalízala o cancélala.
     */
    public function destroy(Ruta $ruta): JsonResponse
    {
        abort_if($ruta->activa(), 409, 'No se puede eliminar una ruta en curso. Finalízala o cancélala primero.');

        $ruta->delete();

        return response()->json(['message' => 'Ruta eliminada.']);
    }

    /**
     * Optimizar el orden de las paradas.
     *
     * Calcula el mejor orden, las distancias, la duración, la ETA de cada
     * parada y el trazo. Con Google Maps configurado usa tráfico real; si
     * no (o si Google falla) usa el cálculo local (`optimizada_con`). En una
     * ruta en curso solo reordena lo pendiente desde la última posición del
     * vehículo. Todas las paradas pendientes necesitan coordenadas.
     */
    public function optimizar(Ruta $ruta): RutaResource
    {
        $reoptimizada = $this->operar(fn () => $this->planeacion->optimizar($ruta));

        $ruta->load(['vehicle', 'chofer', 'paradas.items']);

        return (new RutaResource($ruta))->additional([
            'message' => 'Orden de paradas '.($reoptimizada ? 'reoptimizado' : 'optimizado').' ('.$ruta->distanciaKm().' km, '.$ruta->duracionTexto().').',
        ]);
    }

    /**
     * Iniciar ruta.
     *
     * Solo una ruta programada (con chofer y vehículo). Pasa a `en_ruta`, la
     * primera parada queda "en camino" y el chofer ya puede mandar GPS.
     */
    public function iniciar(Request $request, Ruta $ruta, OperacionDeRutas $op): RutaResource
    {
        $this->operar(fn () => $op->iniciar($ruta, $request->user()->id));

        return (new RutaResource($ruta->fresh(['vehicle', 'chofer', 'paradas'])))
            ->additional(['message' => 'La ruta salió. El chofer ya puede reportar su posición.']);
    }

    /**
     * Finalizar ruta.
     *
     * Cierra una ruta en curso aunque queden paradas sin resolver; fija los
     * kilómetros reales según el GPS. (Cuando se resuelve la última parada
     * la ruta se finaliza sola.)
     */
    public function finalizar(Request $request, Ruta $ruta, OperacionDeRutas $op): RutaResource
    {
        $this->operar(fn () => $op->finalizar($ruta, $request->user()->id));

        return (new RutaResource($ruta->fresh(['vehicle', 'chofer', 'paradas'])))
            ->additional(['message' => 'Ruta finalizada.']);
    }

    /**
     * Cancelar ruta.
     *
     * Cualquier ruta que no esté ya finalizada o cancelada.
     */
    public function cancelar(Request $request, Ruta $ruta, OperacionDeRutas $op): RutaResource
    {
        $this->operar(fn () => $op->cancelar($ruta, $request->user()->id));

        return (new RutaResource($ruta->fresh(['vehicle', 'chofer', 'paradas'])))
            ->additional(['message' => 'Ruta cancelada.']);
    }

    // ------------------------------------------------------------------

    /** Vehículo o chofer ya en una ruta en curso: 422 en el campo, como el error del formulario web. */
    private function sinChoque(array $data, ?Ruta $ruta = null): void
    {
        if ($choque = $this->planeacion->choque($data, $ruta)) {
            throw ValidationException::withMessages([$choque[0] => $choque[1]]);
        }
    }
}
