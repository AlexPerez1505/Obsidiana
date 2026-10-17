<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventario\ProductoDetalleResource;
use App\Http\Resources\Api\V1\Inventario\ProductoResource;
use App\Models\Producto;
use App\Services\Inventario\ProductosDeInventario;
use App\Support\EdicionPropia;
use App\Support\GeneradorDeSeries;
use App\Support\PrecioVisible;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Productos del inventario: cada modelo de equipo con su stock, contado a
 * partir de sus piezas. El precio solo se ve con `precios.ver` y solo se
 * define con `precios.editar`.
 *
 * @tags Inventario · Productos
 */
class ProductoController extends Controller
{
    use ConstruyeCatalogoEquipo, ListadoApi;

    /**
     * Las series pueden llegar como texto, igual que en la web (una por
     * línea o separadas por coma), o como arreglo, que es lo natural en
     * JSON. Las dos terminan en el mismo texto que procesa el servicio.
     */
    public const REGLAS_SERIES = [
        'series_texto' => ['nullable', 'string'],
        'series' => ['nullable', 'array'],
        'series.*' => ['nullable', 'string', 'max:255'],
    ];

    public static function seriesTexto(array $data): ?string
    {
        return ! empty($data['series'])
            ? implode("\n", array_filter($data['series']))
            : ($data['series_texto'] ?? null);
    }

    public function __construct(private ProductosDeInventario $productos) {}

    /**
     * Listar productos.
     *
     * `buscar` busca en tipo, marca, modelo y series. `ubicacion=congreso`
     * deja solo los que tienen piezas en un congreso; `sin_ficha=1`, los que
     * todavía no tienen ficha técnica. Con `con_piezas=1` cada producto
     * trae sus piezas disponibles (con su entrada y congreso).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'ubicacion' => ['nullable', 'in:congreso'],
            'sin_ficha' => ['nullable', 'boolean'],
            'con_piezas' => ['nullable', 'boolean'],
            'equipment_type_id' => ['nullable', 'integer'],
            'equipment_model_id' => ['nullable', 'integer'],
        ]);

        $query = Producto::query()
            ->when($request->boolean('con_piezas'), fn ($q) => $q->with('serialesDisponibles.entrada', 'serialesDisponibles.congress'))
            ->when($request->boolean('sin_ficha'), fn ($q) => $q->whereDoesntHave('fichaTecnica'))
            ->when($request->query('equipment_type_id'), fn ($q, $id) => $q->where('equipment_type_id', $id))
            ->when($request->query('equipment_model_id'), fn ($q, $id) => $q->where('equipment_model_id', $id));

        $this->productos->filtrar($query, $request->query('buscar'), $request->query('ubicacion'));

        // Ordenar por precio también lo revelaría: solo para quien lo ve.
        $campos = ['id', 'tipo_equipo', 'subtipo', 'marca', 'modelo', 'stock', 'created_at', 'updated_at'];
        if (PrecioVisible::para($request->user())) {
            $campos[] = 'precio';
        }

        $this->ordenar($query, $request, $campos, '-created_at');

        return ProductoResource::collection($query->paginate($this->porPagina($request)))
            ->additional(['resumen' => ['piezas_en_congreso' => $this->productos->piezasEnCongreso()]]);
    }

    /**
     * Catálogos para dar de alta o editar un producto.
     *
     * El árbol tipo → subtipo → marca → modelo, los valores ya usados de
     * cada uno y si el usuario puede ver y definir precios.
     */
    public function catalogos(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->can('inventario.registrar') || $request->user()->can('inventario.editar'),
            403,
        );

        return response()->json(['data' => [
            'catalogo' => $this->catalogoEquipo(),
            'opciones' => $this->productos->opciones(),
            've_precio' => PrecioVisible::para($request->user()),
            'puede_definir_precio' => PrecioVisible::editable($request->user()),
        ]]);
    }

    /**
     * Buscar producto por modelo.
     *
     * Si ese modelo ya está en el inventario, devuelve el producto para
     * autocompletar el alta y el siguiente número de serie sugerido.
     * `pide_precio` dice si el formulario debe preguntar el precio (solo si
     * el usuario puede definirlo y el modelo todavía no tiene uno).
     */
    public function buscarPorModelo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_model_id' => ['required', 'exists:equipment_models,id'],
        ]);

        $producto = $this->productos->porModelo((int) $data['equipment_model_id']);

        return response()->json(['data' => [
            'existe' => $producto !== null,
            'tiene_precio' => $producto?->precio !== null,
            'pide_precio' => PrecioVisible::sePide($producto, $request->user()),
            'no_serie_sugerido' => $producto ? $this->productos->siguienteSerial($producto) : null,
            'producto' => $producto ? new ProductoResource($producto) : null,
        ]]);
    }

    /**
     * Generar series propias.
     *
     * Para equipo que no trae serial de fábrica: arma `cantidad` series con
     * el catálogo elegido, sin el tipo al frente (CAM-STR-1588-0001…), y un consecutivo que no
     * choca con las que ya existen. No guarda nada.
     */
    public function generarSeries(Request $request): JsonResponse
    {
        $data = $request->validate(ProductosDeInventario::REGLAS_GENERAR_SERIES);

        $prefijo = GeneradorDeSeries::prefijo(
            $data['equipment_type_id'] ?? null,
            $data['subtype_id'] ?? null,
            $data['brand_id'] ?? null,
            $data['equipment_model_id'] ?? null,
        );

        return response()->json(['data' => [
            'prefijo' => $prefijo,
            'series' => GeneradorDeSeries::generar($prefijo, (int) $data['cantidad']),
        ]]);
    }

    /**
     * Dar de alta un producto.
     *
     * Alta directa, sin evidencia (la que lleva fotos y firma es la
     * entrada). `stock` es cuántas piezas se crean; sus series van en
     * `series_texto` (una por línea o separadas por coma) o en `series[]`,
     * y si solo viene una se numera en secuencia. Si el modelo ya existe,
     * las piezas se suman a ese producto en vez de duplicarlo. La imagen va
     * por multipart.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(ProductosDeInventario::reglas() + self::REGLAS_SERIES);
        $data = ProductosDeInventario::filtrarPrecio($data, $request->user());

        $series = self::seriesTexto($data);
        unset($data['series'], $data['series_texto']);

        $resultado = $this->productos->registrar($data, $series, $request->file('imagen'));

        return (new ProductoResource($resultado['producto']->fresh()))
            ->additional(['message' => $resultado['mensaje']])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ver un producto.
     *
     * Su kardex: todas sus piezas (con su entrada, procesos y venta), la
     * bitácora de entradas y salidas, y los conteos de los que sale el
     * stock.
     */
    public function show(Producto $producto): ProductoDetalleResource
    {
        $kardex = $this->productos->kardex($producto);

        return new ProductoDetalleResource($producto, $kardex);
    }

    /**
     * Editar un producto.
     *
     * Solo los datos generales; las piezas se manejan en
     * `/productos/{producto}/seriales`. `precio` solo cambia si se manda y
     * el usuario tiene `precios.editar`. Para cambiar la imagen manda
     * multipart por POST con `_method=PUT`.
     */
    public function update(Request $request, Producto $producto): ProductoResource
    {
        abort_unless(EdicionPropia::puedeEditarProducto($request->user(), $producto), 403, EdicionPropia::mensaje('editar'));

        // El stock sale de las piezas: aquí se ignora y no hace falta mandarlo.
        $reglas = array_merge(ProductosDeInventario::reglas(), ['stock' => ['sometimes', 'integer', 'min:0']]);

        $data = $request->validate($reglas);

        // Sin permiso, el precio que ya tiene se queda como está.
        if (! PrecioVisible::editable($request->user())) {
            unset($data['precio']);
        }

        $this->productos->actualizar($producto, $data, $request->file('imagen'));

        return (new ProductoResource($producto->fresh()))
            ->additional(['message' => 'Producto actualizado correctamente.']);
    }

    /**
     * Eliminar un producto.
     *
     * Se van con él todas sus piezas. Lo puede quien tiene
     * inventario.eliminar, o quien lo dio de alta durante sus primeras
     * EdicionPropia::HORAS horas.
     */
    public function destroy(Request $request, Producto $producto): JsonResponse
    {
        abort_unless(EdicionPropia::puedeEliminarProducto($request->user(), $producto), 403, EdicionPropia::mensaje('eliminar'));

        $producto->delete();

        return response()->json(['message' => 'Producto eliminado correctamente.']);
    }
}
