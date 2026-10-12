<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Services\Inventario\OperacionRechazada;
use App\Services\Inventario\PinDeAprobacion;
use App\Services\Inventario\ProductosDeInventario;
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
     */
    public function edit(Producto $producto): View
    {
        return view('structure.gestion_Inventario.productos.edit', [
            'producto' => $producto,
            'seriales' => $producto->seriales()->orderByDesc('id')->get(),
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
        $data = $this->validated($request);

        $this->productos->actualizar($producto, $data, $request->file('imagen'));

        return redirect()->route('inventory.productos.index')->with('status', 'Producto actualizado correctamente.');
    }

    /**
     * Elimina un producto.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()->route('inventory.productos.index')->with('status', 'Producto eliminado correctamente.');
    }

    /**
     * Agrega unidades (con o sin serial) a un producto ya existente, desde
     * la pantalla de edición.
     */
    public function agregarSeriales(Request $request, Producto $producto): RedirectResponse
    {
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
    public function eliminarSerial(ProductoSerial $serial): RedirectResponse
    {
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
     * La serie se construye con el catálogo elegido (tipo, subtipo, marca,
     * modelo) más un consecutivo que no choque con las que ya existen.
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
     * Corrige el número de serie y/o la foto de una unidad ya guardada.
     * Pide PIN de aprobación (o contraseña, si el usuario no tiene PIN
     * configurado), igual que las demás acciones destructivas/sensibles
     * del sistema, porque altera un dato que ya quedó como evidencia.
     */
    public function actualizarSerial(Request $request, ProductoSerial $serial): RedirectResponse
    {
        $data = $request->validate(ProductosDeInventario::REGLAS_ACTUALIZAR_SERIAL);

        if (! PinDeAprobacion::valido(auth()->user(), $data['password'])) {
            return back()->withErrors(['password' => PinDeAprobacion::ERROR]);
        }

        try {
            $this->productos->actualizarSerial($serial, $data['no_serie'] ?? null, $request->file('foto'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Unidad actualizada correctamente.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(ProductosDeInventario::reglas());

        // El formulario no dibuja el campo para quien no puede definir precios;
        // si aun así llegó uno, se descarta en vez de confiar en él.
        return ProductosDeInventario::filtrarPrecio($data, $request->user());
    }
}
