<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventario\FichaTecnicaResource;
use App\Models\FichaTecnica;
use App\Services\Inventario\FichasTecnicas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Fichas técnicas: un título y su PDF, ligadas a un producto (cada producto
 * tiene a lo más una) o a un paquete. Cotizaciones y ventas las adjuntan.
 *
 * @tags Inventario · Fichas técnicas
 */
class FichaTecnicaController extends Controller
{
    use ListadoApi;

    /**
     * Las reglas de la web más la liga en sus dos columnas, que es lo
     * natural por la API. `item` ("producto:5", como el select de la web)
     * también se acepta.
     */
    public const REGLAS_LIGA = [
        'producto_id' => ['nullable', 'integer', 'exists:productos,id', 'prohibits:paquete_id'],
        'paquete_id' => ['nullable', 'integer', 'exists:paquetes,id'],
    ];

    public const MENSAJES = FichasTecnicas::MENSAJES + [
        'producto_id.prohibits' => 'Liga la ficha a un producto o a un paquete, no a los dos.',
    ];

    public function __construct(private FichasTecnicas $fichas) {}

    /**
     * Listar fichas técnicas.
     *
     * Por título. `buscar` busca en título y contenido; `activo`,
     * `producto_id` y `paquete_id` filtran. `resumen` cuenta todas las
     * fichas y cuántas tienen PDF.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
            'producto_id' => ['nullable', 'integer'],
            'paquete_id' => ['nullable', 'integer'],
        ]);

        $query = FichaTecnica::query()->with(['producto', 'paquete'])
            ->when($request->query('buscar'), fn ($q, $b) => $q->where(fn ($w) => $w
                ->where('titulo', 'like', "%{$b}%")
                ->orWhere('contenido', 'like', "%{$b}%")))
            ->when($request->filled('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
            ->when($request->query('producto_id'), fn ($q, $id) => $q->where('producto_id', $id))
            ->when($request->query('paquete_id'), fn ($q, $id) => $q->where('paquete_id', $id));

        $this->ordenar($query, $request, ['titulo', 'created_at', 'updated_at', 'id'], 'titulo');

        return FichaTecnicaResource::collection($query->paginate($this->porPagina($request)))
            ->additional(['resumen' => [
                'total' => FichaTecnica::count(),
                'con_pdf' => FichaTecnica::whereNotNull('archivo')->count(),
            ]]);
    }

    /**
     * Ver una ficha técnica.
     */
    public function show(FichaTecnica $ficha): FichaTecnicaResource
    {
        return new FichaTecnicaResource($ficha->load(['producto', 'paquete']));
    }

    /**
     * Dar de alta una ficha técnica.
     *
     * Multipart con el PDF en `archivo`. Se liga a un producto
     * (`producto_id`) o a un paquete (`paquete_id`), no a los dos; un
     * producto que ya tiene ficha no acepta otra. `activo` es verdadero si
     * no se manda. Para ofrecer solo productos sin ficha usa
     * `GET /productos?sin_ficha=1`.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(FichasTecnicas::reglas(true) + self::REGLAS_LIGA, self::MENSAJES);
        $data = $this->normalizar($request, $data);

        $ficha = $this->fichas->crear($data, $request->file('archivo'));

        return (new FichaTecnicaResource($ficha->load(['producto', 'paquete'])))
            ->additional(['message' => 'Ficha técnica guardada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar una ficha técnica.
     *
     * El PDF es opcional: si no se manda se conserva (para mandarlo usa
     * multipart por POST con `_method=PUT`). Lo que no se mande de
     * `activo`, `producto_id` y `paquete_id` se queda como estaba; mandar
     * uno de los dos últimos reemplaza la liga.
     */
    public function update(Request $request, FichaTecnica $ficha): FichaTecnicaResource
    {
        $data = $request->validate(FichasTecnicas::reglas(false) + self::REGLAS_LIGA, self::MENSAJES);
        $data = $this->normalizar($request, $data, $ficha);

        $this->fichas->actualizar($ficha, $data, $request->hasFile('archivo') ? $request->file('archivo') : null);

        return (new FichaTecnicaResource($ficha->fresh()->load(['producto', 'paquete'])))
            ->additional(['message' => 'Ficha técnica actualizada correctamente.']);
    }

    /**
     * Eliminar una ficha técnica.
     *
     * Borra también su PDF.
     */
    public function destroy(FichaTecnica $ficha): JsonResponse
    {
        $this->fichas->eliminar($ficha);

        return response()->json(['message' => 'Ficha técnica eliminada correctamente.']);
    }

    /**
     * Descargar el PDF.
     *
     * El mismo archivo que la web, con un nombre legible sacado del título.
     */
    public function descargar(FichaTecnica $ficha): StreamedResponse
    {
        if (! $this->fichas->tienePdf($ficha)) {
            throw new HttpException(404, 'Esta ficha técnica no tiene su PDF en el servidor.');
        }

        return $this->fichas->descargar($ficha);
    }

    /**
     * Lo que no se mandó se queda como estaba (o con su valor de alta) y
     * un producto no puede quedar con dos fichas.
     */
    private function normalizar(Request $request, array $data, ?FichaTecnica $ficha = null): array
    {
        $data['activo'] = $request->has('activo') ? $request->boolean('activo') : ($ficha?->activo ?? true);

        // Como en la web: "producto:5", "paquete:3" o vacío para no ligarla.
        if (array_key_exists('item', $data)) {
            [$data['producto_id'], $data['paquete_id']] = FichasTecnicas::descomponerItem($data['item']);
        }
        unset($data['item']);

        if ($ficha && ! array_key_exists('producto_id', $data) && ! array_key_exists('paquete_id', $data)) {
            $data['producto_id'] = $ficha->producto_id;
            $data['paquete_id'] = $ficha->paquete_id;
        }

        $data['producto_id'] = isset($data['producto_id']) ? (int) $data['producto_id'] : null;
        $data['paquete_id'] = isset($data['paquete_id']) ? (int) $data['paquete_id'] : null;

        $this->fichas->exigirUnicaPorProducto($data['producto_id'], $ficha, 'producto_id');

        unset($data['archivo']);

        return $data;
    }
}
