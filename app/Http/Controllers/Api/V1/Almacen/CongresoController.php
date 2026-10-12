<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\CategoriaResource;
use App\Http\Resources\Api\V1\Almacen\CongresoParticipanteResource;
use App\Http\Resources\Api\V1\Almacen\CongresoResource;
use App\Models\Category;
use App\Models\Congress;
use App\Models\CongresoParticipante;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use App\Services\Almacen\GestionDeCongresos;
use App\Services\RegresoDeCongresos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Congresos: qué piezas se llevaron (por unidad, con su serie), quién
 * fue, fechas de montaje y costos.
 *
 * Llevar una pieza a un congreso solo la etiqueta: sigue vendible (allá
 * mismo se puede vender) y no cambia de estado. Al terminar, lo que no se
 * vendió se regresa; las vendidas conservan la marca del congreso.
 *
 * Los archivos (`archivos[]`) van en multipart. Para editar con archivos,
 * manda POST con `_method=PUT`: PHP no lee multipart en un PUT.
 *
 * @tags Almacén · Congresos
 */
class CongresoController extends Controller
{
    use ListadoApi;

    public function __construct(private readonly GestionDeCongresos $congresos)
    {
    }

    /**
     * Listar congresos.
     *
     * Por fecha de inicio. `desde`/`hasta` traen los congresos que caen
     * (aunque sea un día) en ese rango. `estado`: upcoming (próximos),
     * active (en curso) o finished (terminados).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Respaldo del aviso de piezas sin regresar, igual que la pantalla:
        // trae su propio candado, así que consultar seguido no manda de más.
        app(RegresoDeCongresos::class)->avisarSiToca();

        $filtros = $request->validate([
            // Nombre, lugar o número de stand.
            'buscar' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'estado' => ['nullable', Rule::in(['upcoming', 'active', 'finished'])],
            'categoria_id' => ['nullable', 'integer'],
        ]);

        $query = Congress::query()
            ->with('category')
            ->withCount(['unidadesPresentes', 'unidadesVendidas'])
            ->when($filtros['buscar'] ?? null, fn ($q, string $texto) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', "%{$texto}%")
                ->orWhere('direccion', 'like', "%{$texto}%")
                ->orWhere('num_stand', 'like', "%{$texto}%")))
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha_finalizacion', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $d) => $q->whereDate('fecha_inicio', '<=', $d))
            ->when($filtros['estado'] ?? null, fn ($q, string $e) => $q->enEstado($e))
            ->when($filtros['categoria_id'] ?? null, fn ($q, $id) => $q->where('categoria_id', $id));

        $this->ordenar($query, $request, ['id', 'nombre', 'fecha_inicio', 'fecha_finalizacion', 'created_at'], 'fecha_inicio');

        return CongresoResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Catálogos del formulario.
     *
     * Lo que pide la pantalla de alta: categorías, productos (para elegir
     * de cuál se llevan unidades) y usuarios que pueden ir.
     */
    public function catalogos(): JsonResponse
    {
        return response()->json(['data' => [
            'categorias' => CategoriaResource::collection(Category::query()->orderBy('nombre')->get()),
            'productos' => Producto::orderBy('tipo_equipo')->get()->map(fn (Producto $p) => [
                'id' => $p->id,
                'nombre' => $p->nombreVisible(),
                'tipo_equipo' => $p->tipo_equipo,
                'subtipo' => $p->subtipo,
                'marca' => $p->marca,
                'modelo' => $p->modelo,
            ]),
            'usuarios' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]]);
    }

    /**
     * Unidades disponibles de un producto.
     *
     * Las piezas que se pueden llevar (disponibles y sin congreso), con su
     * foto, para elegir a mano cuáles van.
     */
    public function unidadesDisponibles(Request $request): JsonResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
        ]);

        return response()->json([
            'data' => $this->congresos->unidadesDisponibles(Producto::findOrFail($data['producto_id'])),
        ]);
    }

    /**
     * Ver un congreso.
     *
     * Con lo que sigue allá agrupado por producto (y cada unidad), cuántas
     * se vendieron en el evento, participantes y usuarios asignados.
     */
    public function show(Congress $congreso): CongresoResource
    {
        return new CongresoResource($this->detalle($congreso));
    }

    /**
     * Crear un congreso.
     *
     * Desde el alta ya se pueden mandar las piezas que van (`serial_ids`) y
     * los usuarios (`user_ids`); las piezas que ya no estén disponibles se
     * ignoran.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->congresos->normalizar($request, $request->validate(GestionDeCongresos::REGLAS, GestionDeCongresos::MENSAJES));

        $request->validate(GestionDeCongresos::REGLAS_ALTA_INICIAL);

        $congreso = $this->congresos->crear(
            $data,
            (array) $request->file('archivos', []),
            (array) $request->input('serial_ids', []),
            (array) $request->input('user_ids', []),
        );

        return (new CongresoResource($this->detalle($congreso)))
            ->additional(['message' => 'Congreso guardado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar un congreso.
     *
     * Se manda el congreso completo, como en el formulario. `archivos[]`
     * agrega archivos y `quitar_archivos[]` (rutas de `archivos[].path`)
     * borra los que ya tenía.
     */
    public function update(Request $request, Congress $congreso): CongresoResource
    {
        $data = $this->congresos->normalizar($request, $request->validate(GestionDeCongresos::REGLAS, GestionDeCongresos::MENSAJES));

        $this->congresos->actualizar($congreso, $data, (array) $request->file('archivos', []), (array) $request->input('quitar_archivos', []));

        return $this->respuesta($congreso, 'Congreso actualizado correctamente.');
    }

    /**
     * Eliminar un congreso.
     */
    public function destroy(Congress $congreso): JsonResponse
    {
        $congreso->delete();

        return response()->json(['message' => 'Congreso eliminado correctamente.']);
    }

    /**
     * Llevar unidades al congreso.
     *
     * Las piezas elegidas a mano (`serial_ids`). Solo se llevan las que
     * siguen disponibles y sin congreso; si ninguna lo está, 422.
     */
    public function agregarUnidades(Request $request, Congress $congreso): CongresoResource
    {
        $data = $request->validate([
            'serial_ids' => ['required', 'array', 'min:1'],
            'serial_ids.*' => ['integer', 'exists:producto_seriales,id'],
        ], [
            'serial_ids.required' => 'Elige al menos una unidad de las que se muestran.',
        ]);

        $cuantas = $this->congresos->agregarUnidades($congreso, $data['serial_ids']);

        if ($cuantas === 0) {
            throw ValidationException::withMessages([
                'serial_ids' => 'Esas unidades ya no están disponibles.',
            ]);
        }

        return $this->respuesta($congreso, $cuantas.' unidad(es) agregadas al congreso.');
    }

    /**
     * Regresar una unidad.
     *
     * La pieza ya no está "en" el congreso: vuelve al almacén.
     */
    public function quitarUnidad(Congress $congreso, ProductoSerial $serial): CongresoResource
    {
        abort_unless((int) $serial->congress_id === (int) $congreso->id, 404, 'Esa pieza no está en este congreso.');

        $this->congresos->quitarUnidad($serial);

        return $this->respuesta($congreso, 'Unidad regresada del congreso.');
    }

    /**
     * Regresar todas las unidades.
     *
     * Cierra el ciclo al terminar el congreso: todo lo que no se vendió
     * vuelve al almacén. Las vendidas conservan la marca. Si no hay nada
     * allá, 409.
     */
    public function regresarTodas(Congress $congreso): CongresoResource
    {
        $cuantas = $this->congresos->regresarTodas($congreso);

        abort_if($cuantas === 0, 409, 'Este congreso no tiene piezas por regresar.');

        return $this->respuesta($congreso, "Se regresaron {$cuantas} pieza(s) al almacén.");
    }

    /**
     * Agregar un participante.
     *
     * Quien asistió (ponente, distribuidor...). Es un registro libre del
     * congreso, no un cliente ni un usuario.
     */
    public function agregarParticipante(Request $request, Congress $congreso): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'rol' => ['nullable', 'string', 'max:100'],
            'empresa' => ['nullable', 'string', 'max:255'],
        ]);

        $participante = $congreso->participantes()->create($data);

        return (new CongresoParticipanteResource($participante))
            ->additional(['message' => 'Participante agregado.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Quitar un participante.
     */
    public function quitarParticipante(Congress $congreso, CongresoParticipante $participante): JsonResponse
    {
        abort_unless((int) $participante->congress_id === (int) $congreso->id, 404, 'Ese participante no es de este congreso.');

        $participante->delete();

        return response()->json(['message' => 'Participante eliminado.']);
    }

    /**
     * Asignar un usuario.
     *
     * Usuarios del sistema que van al congreso: son a quienes se les avisa
     * si al terminar quedan piezas sin regresar.
     */
    public function agregarUsuario(Request $request, Congress $congreso): CongresoResource
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $congreso->notifiedUsers()->syncWithoutDetaching([$data['user_id']]);

        return $this->respuesta($congreso, 'Usuario agregado al congreso.');
    }

    /**
     * Quitar un usuario asignado.
     */
    public function quitarUsuario(Congress $congreso, User $user): CongresoResource
    {
        $congreso->notifiedUsers()->detach($user);

        return $this->respuesta($congreso, 'Usuario quitado del congreso.');
    }

    private function detalle(Congress $congreso): Congress
    {
        return $congreso->load([
            'category',
            'unidadesPresentes.producto',
            'participantes' => fn ($q) => $q->latest(),
            'notifiedUsers',
        ])->loadCount(['unidadesPresentes', 'unidadesVendidas']);
    }

    private function respuesta(Congress $congreso, string $mensaje): CongresoResource
    {
        return (new CongresoResource($this->detalle($congreso->refresh())))
            ->additional(['message' => $mensaje]);
    }
}
