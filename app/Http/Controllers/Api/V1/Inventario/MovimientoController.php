<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventario\MovimientoDetalleResource;
use App\Http\Resources\Api\V1\Inventario\MovimientoResource;
use App\Models\InventoryMovement;
use App\Models\PiezaProceso;
use App\Services\Inventario\EntradasDeInventario;
use App\Services\Inventario\VideoPorPedazos;
use App\Support\ChecklistRecepcion;
use App\Support\EdicionPropia;
use App\Support\PrecioVisible;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Entradas y salidas de inventario.
 *
 * Una entrada da de alta stock nuevo con evidencia por pieza (1 a 3 fotos,
 * video opcional) y la firma de quien recibió. Las salidas nacen solas al
 * registrar una venta: aquí solo se consultan.
 *
 * @tags Inventario · Entradas y salidas
 */
class MovimientoController extends Controller
{
    use ConstruyeCatalogoEquipo, ListadoApi;

    /**
     * Listar movimientos.
     *
     * Entradas y salidas, de la más reciente a la más vieja. Filtra por
     * `tipo` (entrada, salida, transferencia), rango de `desde`/`hasta`
     * (fecha del movimiento) y `buscar` (folio, equipo o referencia de
     * venta). `resumen` trae las métricas de todo el inventario, no solo de
     * la página.
     */
    public function index(Request $request, EntradasDeInventario $entradas): AnonymousResourceCollection
    {
        $request->validate([
            'tipo' => ['nullable', Rule::in([InventoryMovement::TYPE_ENTRY, InventoryMovement::TYPE_EXIT, InventoryMovement::TYPE_TRANSFER])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $query = InventoryMovement::query()->with('creator');

        $entradas->filtrar($query, $request->query('tipo'), $request->query('desde'), $request->query('hasta'));

        if ($buscar = $request->query('buscar')) {
            $query->where(fn ($q) => $q->where('folio', 'like', "%{$buscar}%")
                ->orWhere('item_name', 'like', "%{$buscar}%")
                ->orWhere('reference', 'like', "%{$buscar}%"));
        }

        $this->ordenar($query, $request, ['movement_date', 'folio', 'quantity', 'created_at', 'id'], '-movement_date')
            ->orderByDesc('id');

        return MovimientoResource::collection($query->paginate($this->porPagina($request)))
            ->additional(['resumen' => $entradas->resumen()]);
    }

    /**
     * Catálogos para registrar una entrada.
     *
     * Lo que necesita el formulario: el árbol tipo → subtipo → marca →
     * modelo (`subtipos` por id de tipo, `marcas` por id de subtipo,
     * `modelos` por "subtipo-marca"), el checklist de recepción del equipo
     * usado, sus estados generales, los procesos posibles y la firma
     * registrada del usuario (data URL, lista para mandarla en `firma`).
     */
    public function catalogos(Request $request): JsonResponse
    {
        return response()->json(['data' => [
            'catalogo' => $this->catalogoEquipo(),
            'condiciones' => ['nuevo' => 'Nuevo', 'usado' => 'Usado'],
            'checklist' => ChecklistRecepcion::grupos(),
            'respuestas_checklist' => ChecklistRecepcion::ETIQUETAS,
            'estados_generales' => ChecklistRecepcion::ESTADOS,
            'procesos' => PiezaProceso::PROCESOS,
            'puede_definir_precio' => PrecioVisible::editable($request->user()),
            'firma_registrada' => $request->user()->firmaDataUri(),
            'video' => [
                'extensiones' => VideoPorPedazos::EXTENSIONES,
                'pedazo_max_kb' => VideoPorPedazos::PEDAZO_MAX_KB,
                'max_bytes' => VideoPorPedazos::MAX_BYTES,
            ],
        ]]);
    }

    /**
     * Subir un pedazo del video de una pieza.
     *
     * El video se manda en pedazos de hasta 5 MB (multipart, campo
     * `chunk`), todos con el mismo `upload_id`, su `index` (desde 0) y el
     * `total`. Con el último pedazo se ensambla y la respuesta trae
     * `status: "listo"` y `video_path`: esa ruta es la que va en
     * `unidades[N][video_path]` al registrar la entrada. Máximo 150 MB.
     */
    public function subirVideo(Request $request, VideoPorPedazos $videos): JsonResponse
    {
        $data = $request->validate(VideoPorPedazos::reglas());

        return response()->json(['data' => $videos->recibir(
            $request->file('chunk'),
            $data['upload_id'],
            (int) $data['index'],
            (int) $data['total'],
            $data['extension'],
        )]);
    }

    /**
     * Registrar una entrada.
     *
     * Multipart. Da de alta (o acumula, si ya existe la misma combinación
     * de catálogo) el producto y crea una pieza por cada renglón de
     * `unidades`, cada una con 1 a 3 fotos en `unidades[N][evidencias][]`
     * y, si se subió, su `video_path`. Debe haber tantas unidades como
     * `cantidad`. Si solo la primera trae `no_serie`, el resto se numera en
     * secuencia. `firma` es la firma de quien recibe como data URL base64
     * (`data:image/png;base64,...`).
     *
     * Equipo usado (`condicion=usado`): pide `estado_general`, el
     * `checklist` (`checklist[llave][r]` = si|no|na y `[nota]` opcional) y,
     * si necesita trabajo, la ruta en `procesos[]`; cada pieza nace parada
     * en el primero. `precio` solo se toma con `precios.editar`.
     *
     * Las series que ya existían se guardan vacías y se avisan en
     * `advertencias`, sin perder la pieza ni su evidencia.
     */
    public function store(Request $request, EntradasDeInventario $entradas): JsonResponse
    {
        $usado = $request->input('condicion') === 'usado';

        $data = $request->validate(EntradasDeInventario::reglas($usado), EntradasDeInventario::MENSAJES);

        $resultado = $entradas->registrar($request, $data);

        return (new MovimientoResource($resultado['movimiento']->load(['creator', 'seriales.procesos'])))
            ->additional(['message' => $resultado['mensaje'], 'advertencias' => $resultado['advertencias']])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ver un movimiento.
     *
     * Con sus piezas (en una entrada, las que llegaron; en una salida, las
     * que se vendieron en ese renglón), el producto y, si es una salida por
     * venta, su orden de salida.
     */
    public function show(InventoryMovement $movimiento, EntradasDeInventario $entradas): MovimientoDetalleResource
    {
        $movimiento->load(['creator', 'seriales.procesos']);

        // Las piezas que enseña el detalle no son siempre las de la
        // relación: en una salida son las del renglón de venta.
        $piezas = new EloquentCollection($movimiento->unidadesRelacionadas()->all());
        $movimiento->setRelation('seriales', $piezas->loadMissing('procesos'));

        return new MovimientoDetalleResource($movimiento, $entradas->ordenDeSalida($movimiento));
    }

    /**
     * Hoja de etiquetas.
     *
     * La misma página HTML de la web, lista para imprimir: el QR de cada
     * pieza de la entrada (lleva a su ficha pública) con su código.
     */
    public function etiquetas(InventoryMovement $movimiento): Response
    {
        $piezas = $movimiento->seriales()->with('producto')->orderBy('id')->get();

        return response()->view('structure.gestion_Inventario.entrada_salida.etiquetas', [
            'movimiento' => $movimiento,
            'piezas' => $piezas,
        ]);
    }

    /**
     * Eliminar un movimiento.
     *
     * Ya no pide PIN. Lo puede quien tiene inventario.eliminar, o quien
     * registró la entrada durante sus primeras EdicionPropia::HORAS horas.
     * Borra las piezas de la entrada y toda su evidencia y recalcula el
     * stock. Si alguna pieza ya se vendió responde 409.
     */
    public function destroy(Request $request, InventoryMovement $movimiento, EntradasDeInventario $entradas): JsonResponse
    {
        abort_unless(EdicionPropia::puedeEliminarMovimiento($request->user(), $movimiento), 403, EdicionPropia::mensaje('eliminar'));

        $entradas->eliminar($movimiento);

        return response()->json(['message' => "Movimiento {$movimiento->folio} eliminado."]);
    }
}
