<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Services\Inventario\OperacionRechazada;
use App\Services\Inventario\ProductosDeInventario;
use App\Support\EdicionPropia;
use App\Support\GeneradorDeSeries;
use App\Support\PrecioVisible;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Productos del inventario. Las altas, la acumulación por modelo y el
 * manejo de cada unidad viven en ProductosDeInventario, compartido con la
 * API: aquí solo queda cómo se ve en pantalla.
 */
class ProductoController extends Controller
{
    use ConstruyeCatalogoEquipo;

    public function __construct(private ProductosDeInventario $productos)
    {
    }

    /**
     * Lista los productos del inventario.
     */
    public function index(Request $request): View
    {
        // Se trae también la entrada y el congreso de cada unidad: el modal
        // muestra la fecha de llegada y avisa si la pieza no está en el
        // almacén. Sin esto era una consulta por unidad.
        $query = Producto::query()->with('serialesDisponibles.entrada', 'serialesDisponibles.congress')->latest();

        $this->productos->filtrar($query, $request->get('search'), $request->get('ubicacion'));

        $productos = $query->paginate(20)->withQueryString();

        return view('structure.gestion_Inventario.productos.index', [
            'productos' => $productos,
            'filters' => $request->only('search', 'ubicacion'),
            'enCongreso' => $this->productos->piezasEnCongreso(),
        ]);
    }

    /**
     * Muestra el formulario de creación de producto.
     */
    public function create(): View
    {
        return view('structure.gestion_Inventario.productos.create', [
            'productoOptions' => $this->productos->opciones(),
            'catalogo' => $this->catalogoEquipo(),
        ]);
    }

    /**
     * Guarda un nuevo producto. Si el modelo ya existe en el inventario,
     * las unidades nuevas se agregan a la fila que ya hay.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $resultado = $this->productos->registrar($data, $request->input('series_texto', ''), $request->file('imagen'));
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('inventory.productos.index')->with('status', $resultado['mensaje']);
    }

    /**
     * Muestra el formulario de edición de producto.
     *
     * Entra quien puede editar el producto, y también quien registró piezas
     * de él hace menos de EdicionPropia::HORAS horas: aunque el producto no
     * sea suyo, sí puede corregir las piezas que él capturó.
     */
    public function edit(Request $request, Producto $producto): View
    {
        $user = $request->user();
        $seriales = $producto->seriales()->with('entrada')->orderByDesc('id')->get();

        $puedeProducto = EdicionPropia::puedeEditarProducto($user, $producto);

        abort_unless(
            $puedeProducto
            || $seriales->contains(fn ($s) => EdicionPropia::puedeEditarPieza($user, $s) || EdicionPropia::puedeEliminarPieza($user, $s)),
            403,
            EdicionPropia::mensaje('editar')
        );

        return view('structure.gestion_Inventario.productos.edit', [
            'producto' => $producto,
            'seriales' => $seriales,
            'puedeProducto' => $puedeProducto,
            'puedeAgregar' => EdicionPropia::puedeAgregarPiezas($user, $producto),
            'vencePlazo' => EdicionPropia::venceEn($user, $producto->created_by, $producto->created_at),
            'productoOptions' => $this->productos->opciones(),
            'catalogo' => $this->catalogoEquipo(),
        ]);
    }

    /**
     * Actualiza los datos generales de un producto existente. El stock y
     * los números de serie se manejan aparte, unidad por unidad.
     */
    public function update(Request $request, Producto $producto): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeEditarProducto($request->user(), $producto), 403, EdicionPropia::mensaje('editar'));

        $data = $this->validated($request);

        $this->productos->actualizar($producto, $data, $request->file('imagen'));

        return redirect()->route('inventory.productos.index')->with('status', 'Producto actualizado correctamente.');
    }

    /**
     * Elimina un producto.
     */
    public function destroy(Request $request, Producto $producto): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeEliminarProducto($request->user(), $producto), 403, EdicionPropia::mensaje('eliminar'));

        $producto->delete();

        return redirect()->route('inventory.productos.index')->with('status', 'Producto eliminado correctamente.');
    }

    /**
     * Agrega unidades (con o sin serial) a un producto ya existente, desde
     * la pantalla de edición.
     */
    public function agregarSeriales(Request $request, Producto $producto): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeAgregarPiezas($request->user(), $producto), 403, EdicionPropia::mensaje('agregar piezas a'));

        $data = $request->validate(ProductosDeInventario::REGLAS_AGREGAR_UNIDADES);

        try {
            $this->productos->agregarUnidades($producto, (int) $data['cantidad'], $data['series_texto'] ?? '');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', "Se agregaron {$data['cantidad']} unidad(es) al inventario.");
    }

    /**
     * Quita del inventario una unidad que nunca se llegó a vender (por
     * ejemplo, si se dio de alta por error).
     */
    public function eliminarSerial(Request $request, ProductoSerial $serial): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeEliminarPieza($request->user(), $serial), 403, EdicionPropia::mensaje('eliminar'));

        try {
            $this->productos->eliminarSerial($serial);
        } catch (OperacionRechazada $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }

        return back()->with('status', 'Unidad eliminada del inventario.');
    }

    /**
     * Si ese modelo ya está registrado, se manda lo que ya se sabe de él
     * (precio, descripción...) para autocompletar el alta. El
     * stock y el número de serie no se tocan: son propios de cada unidad.
     */
    public function buscarPorModelo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_model_id' => ['required', 'exists:equipment_models,id'],
        ]);

        $producto = $this->productos->porModelo((int) $data['equipment_model_id']);

        if (! $producto) {
            return response()->json(['existe' => false]);
        }

        /*
        | El precio solo viaja si a quien pregunta le toca verlo. Esconder
        | el campo en el formulario no bastaría: la respuesta de este
        | endpoint se lee desde las herramientas del navegador.
        */
        $vePrecio = PrecioVisible::para($request->user());

        return response()->json([
            'existe' => true,
            've_precio' => $vePrecio,
            'tiene_precio' => $producto->precio !== null,
            'precio' => $vePrecio && $producto->precio !== null ? (float) $producto->precio : null,
            'precio_texto' => PrecioVisible::texto($producto, $request->user()),
            'descripcion' => $producto->descripcion,
            'referencia' => $producto->referencia,
            'imagen' => $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null,
            'stock_actual' => (int) $producto->stock,
            'es_serializado' => (bool) $producto->es_serializado,
            'no_serie_sugerido' => $this->productos->siguienteSerial($producto),
        ]);
    }

    /**
     * Ficha del producto: el kardex.
     *
     * Responde de un vistazo las tres preguntas de almacén: qué entró,
     * cuándo entró y cuánto hay hoy.
     */
    public function show(Producto $producto): View
    {
        $kardex = $this->productos->kardex($producto);

        return view('structure.gestion_Inventario.productos.show', [
            'producto' => $producto,
            'piezas' => $kardex['piezas'],
            'movimientos' => $kardex['movimientos'],
            'conteos' => $kardex['conteos'],
        ]);
    }

    /**
     * Arma series propias para equipo que no trae serial de fábrica.
     *
     * La serie se construye con el catálogo elegido (subtipo, marca y
     * modelo; el tipo ya no va al frente) más un consecutivo que no choque.
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

        $series = GeneradorDeSeries::generar($prefijo, (int) $data['cantidad']);

        return response()->json([
            'prefijo' => $prefijo,
            'series' => $series,
        ]);
    }

    /**
     * Corrige el número de serie y las fotos de una pieza ya guardada.
     *
     * Ya no pide PIN ni contraseña: la puede corregir quien tiene permiso
     * de editar inventario, o quien la registró durante sus primeras
     * EdicionPropia::HORAS horas. Las fotos nuevas se suman a las que ya
     * tiene (hasta 3), no las reemplazan.
     */
    public function actualizarSerial(Request $request, ProductoSerial $serial): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeEditarPieza($request->user(), $serial), 403, EdicionPropia::mensaje());

        $data = $request->validate(ProductosDeInventario::REGLAS_ACTUALIZAR_SERIAL);

        $fotos = array_values(array_filter([...($request->file('fotos') ?? []), $request->file('foto')]));

        // El modal de fotos no manda la serie: si no viene, se conserva.
        $serie = array_key_exists('no_serie', $data) ? $data['no_serie'] : $serial->no_serie;

        try {
            $this->productos->actualizarSerial($serial, $serie, $fotos, $data['quitar_fotos'] ?? []);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Unidad actualizada correctamente.');
    }

    /**
     * Guarda de una vez las series que se cambiaron en la tabla de piezas,
     * sin abrir una por una.
     */
    public function seriesEnLote(Request $request, Producto $producto): RedirectResponse
    {
        $data = $request->validate(ProductosDeInventario::REGLAS_SERIES_EN_LOTE);

        // Cada pieza se revisa por separado: puede que unas sean del
        // usuario (dentro de su plazo) y otras no.
        $ajenas = $producto->seriales()->whereIn('id', array_keys($data['series']))->get()
            ->reject(fn (ProductoSerial $s) => EdicionPropia::puedeEditarPieza($request->user(), $s));

        abort_if($ajenas->isNotEmpty(), 403, EdicionPropia::mensaje());

        try {
            $cambiadas = $this->productos->actualizarSeriesEnLote($producto, $data['series']);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return back()->with('status', $cambiadas
            ? "Se guardaron {$cambiadas} número(s) de serie."
            : 'No había series nuevas que guardar.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(ProductosDeInventario::reglas());

        // El formulario no dibuja el campo para quien no puede definir precios;
        // si aun así llegó uno, se descarta en vez de confiar en él.
        return ProductosDeInventario::filtrarPrecio($data, $request->user());
    }
}
