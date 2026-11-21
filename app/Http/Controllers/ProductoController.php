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
        // Agrupados por tipo (laparoscopía, endoscopía…) y en orden
        // alfabético dentro de cada tipo: subtipo, marca y modelo.
        $query = Producto::query()->with('serialesDisponibles.entrada', 'serialesDisponibles.congress')
            ->orderByRaw("COALESCE(NULLIF(tipo_equipo, ''), 'ZZZ')")
            ->orderBy('subtipo')->orderBy('marca')->orderBy('modelo');

        $filters = $request->validate(ProductosDeInventario::REGLAS_FILTROS_CATALOGO + [
            'search' => ['nullable', 'string', 'max:255'],
            'ubicacion' => ['nullable', 'in:congreso'],
        ]);
        $this->productos->filtrar($query, $filters['search'] ?? null, $filters['ubicacion'] ?? null, $filters);

        $tipo = trim((string) $request->get('tipo', ''));
        $porTipo = (clone $query)->reorder()->selectRaw("COALESCE(NULLIF(tipo_equipo, ''), 'Sin tipo') as tipo, COUNT(*) as n")
            ->groupBy('tipo')->orderBy('tipo')->pluck('n', 'tipo');

        /*
        | Dos vistas. Al entrar: solo las categorías. La lista (barra,
        | paquetes y tabla) sale al abrir una categoría, "Todos los
        | productos", o en cuanto hay búsqueda, filtros o una página.
        */
        $vistaLista = $tipo !== ''
            || $request->query('ver') === 'todos'
            || $request->has('page')
            || collect($filters)->filter(fn ($v) => filled($v))->isNotEmpty();

        if (! $vistaLista) {
            return view('structure.gestion_Inventario.productos.index', [
                'vistaLista' => false,
                'productos' => null,
                'filters' => $filters + ['tipo' => ''],
                'porTipo' => $porTipo,
                'tarjetasTipo' => $this->tarjetasPorTipo(clone $query, $porTipo),
                'paquetesPorTipo' => collect(),
                'enCongreso' => 0,
                'catalogo' => [],
            ]);
        }

        if ($tipo !== '') {
            $tipo === 'Sin tipo'
                ? $query->where(fn ($w) => $w->whereNull('tipo_equipo')->orWhere('tipo_equipo', ''))
                : $query->where('tipo_equipo', $tipo);
        }

        $productos = $query->paginate(50)->withQueryString();

        // Los paquetes, agrupados por el tipo que más traen, con su
        // contenido. Dentro de una categoría, solo los de esa categoría.
        $paquetes = \App\Models\Paquete::with(['productos', 'equipos'])->orderBy('nombre')->get()
            ->groupBy(fn ($p) => $p->tipoPrincipal())->sortKeys();
        if ($tipo !== '') {
            $paquetes = $paquetes->filter(fn ($lista, $t) => mb_strtoupper((string) $t) === mb_strtoupper($tipo));
        }

        return view('structure.gestion_Inventario.productos.index', [
            'vistaLista' => true,
            'productos' => $productos,
            'filters' => $filters + ['tipo' => $tipo],
            'catalogo' => $this->catalogoEquipo(),
            'enCongreso' => $this->productos->piezasEnCongreso(),
            'porTipo' => $porTipo,
            'tarjetasTipo' => collect(),
            'paquetesPorTipo' => $paquetes,
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

        $returnTo = $request->query('return_to');
        $volverUrl = route('inventory.productos.index');
        if (is_string($returnTo) && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            $volverUrl = url($returnTo);
        }

        return view('structure.gestion_Inventario.productos.edit', [
            'producto' => $producto,
            'seriales' => $seriales,
            'puedeProducto' => $puedeProducto,
            'puedeAgregar' => EdicionPropia::puedeAgregarPiezas($user, $producto),
            'vencePlazo' => EdicionPropia::venceEn($user, $producto->created_by, $producto->created_at),
            'productoOptions' => $this->productos->opciones(),
            'catalogo' => $this->catalogoEquipo(),
            'volverUrl' => $volverUrl,
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
     * Muestra u oculta el producto en la tienda en línea. Es un dato del
     * producto, así que lo cambia quien puede editarlo.
     */
    public function tienda(Request $request, Producto $producto): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeEditarProducto($request->user(), $producto), 403, EdicionPropia::mensaje('editar'));

        $data = $request->validate(['en_tienda' => ['required', 'boolean']]);

        $producto->update(['en_tienda' => (bool) $data['en_tienda']]);

        $nombre = $producto->nombreVisible();

        return back()->with('status', $producto->en_tienda
            ? "{$nombre} ya se muestra en la página web."
            : "{$nombre} se ocultó de la página web.");
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
    public function show(Request $request, Producto $producto): View
    {
        $kardex = $this->productos->kardex($producto);

        // Solo aceptamos rutas internas relativas para evitar redirecciones
        // externas. Si no viene de un contexto concreto, vuelve al catálogo.
        $returnTo = $request->query('return_to');
        $volverUrl = route('inventory.productos.index');
        if (is_string($returnTo) && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            $volverUrl = url($returnTo);
        }

        // "También en esta categoría": los de más stock del mismo tipo.
        $relacionados = Producto::query()
            ->where('id', '<>', $producto->id)
            ->when($producto->tipo_equipo, fn ($q) => $q->where('tipo_equipo', $producto->tipo_equipo))
            ->orderByDesc('stock')->orderBy('subtipo')
            ->limit(8)
            ->get();

        return view('structure.gestion_Inventario.productos.show', [
            'producto' => $producto,
            'piezas' => $kardex['piezas'],
            'movimientos' => $kardex['movimientos'],
            'conteos' => $kardex['conteos'],
            'volverUrl' => $volverUrl,
            'relacionados' => $relacionados,
            'enCongresoProducto' => $kardex['piezas']->where('vendido', false)->whereNotNull('congress_id')->count(),
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

        // Solo para enseñar en el botón cuál sigue, sin apartar nada.
        if ($request->boolean('solo_ver')) {
            return response()->json([
                'prefijo' => $prefijo,
                'siguiente' => GeneradorDeSeries::siguiente($prefijo),
            ]);
        }

        $series = GeneradorDeSeries::generar($prefijo, (int) $data['cantidad'], [
            $data['equipment_type_id'] ?? null,
            $data['subtype_id'] ?? null,
            $data['brand_id'] ?? null,
            $data['equipment_model_id'] ?? null,
        ]);

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

    /**
     * Una tarjeta por tipo de equipo: cuántos productos y piezas
     * disponibles hay (con los mismos filtros de búsqueda) y una foto que
     * lo represente. La foto es la del producto con más stock de ese tipo
     * que tenga imagen; se resuelve en una sola consulta, no una por tipo.
     *
     * @return \Illuminate\Support\Collection<int, array{tipo:string, productos:int, disponibles:int, imagen:?string}>
     */
    private function tarjetasPorTipo(\Illuminate\Database\Eloquent\Builder $query, \Illuminate\Support\Collection $porTipo): \Illuminate\Support\Collection
    {
        $tipoSql = "COALESCE(NULLIF(tipo_equipo, ''), 'Sin tipo')";

        $disponibles = (clone $query)->reorder()
            ->selectRaw("{$tipoSql} as tipo, SUM(stock) as piezas")
            ->groupBy('tipo')->pluck('piezas', 'tipo');

        $fotos = Producto::query()
            ->whereNotNull('imagen_path')->where('imagen_path', '<>', '')
            ->orderByDesc('stock')->orderBy('id')
            ->get(['id', 'tipo_equipo', 'imagen_path', 'stock'])
            ->groupBy(fn (Producto $p) => trim((string) $p->tipo_equipo) !== '' ? $p->tipo_equipo : 'Sin tipo')
            ->map(fn ($grupo) => $grupo->first()->imagen_path);

        return $porTipo->map(fn ($n, $tipo) => [
            'tipo' => (string) $tipo,
            'productos' => (int) $n,
            'disponibles' => (int) ($disponibles[$tipo] ?? 0),
            'imagen' => isset($fotos[$tipo]) ? asset('storage/'.$fotos[$tipo]) : null,
        ])->values();
    }
}
