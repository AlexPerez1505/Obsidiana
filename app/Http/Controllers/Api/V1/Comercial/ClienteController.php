<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Comercial\CategoriaClienteResource;
use App\Http\Resources\Api\V1\Comercial\ClienteResource;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\Category;
use App\Models\ClienteSeguimiento;
use App\Models\Congress;
use App\Models\Customer;
use App\Models\User;
use App\Services\Clientes\RegistroClientes;
use App\Services\Clientes\SeguimientosCliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Directorio de clientes y prospectos. Mismas reglas que la web: sin
 * `clientes.ver_todos` cada asesor solo ve (y edita) los que él registró.
 *
 * @tags Clientes
 */
class ClienteController extends Controller
{
    use ListadoApi;

    public function __construct(
        private readonly RegistroClientes $registro,
        private readonly SeguimientosCliente $seguimientos,
    ) {
    }

    /**
     * Listar clientes.
     *
     * Paginado. Sin `clientes.ver_todos` solo trae los clientes que registró
     * el usuario del token. Cada cliente viene con su asesor, categoría,
     * congreso de origen y el próximo seguimiento pendiente.
     *
     * Filtros: `buscar` (nombre, apellido, correo, teléfono, RFC, dirección
     * o nombre del asesor), `etapa`, `activo`, `recibe_promocion`,
     * `categoria_id`, `congreso_id`, `asesor_id`, `direccion` (contiene),
     * `con_seguimiento_pendiente`, `desde` / `hasta` (fecha de alta).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            /** cliente | prospecto */
            'etapa' => ['nullable', Rule::in(array_keys(Customer::ETAPAS))],
            'activo' => ['nullable', 'boolean'],
            'recibe_promocion' => ['nullable', 'boolean'],
            'categoria_id' => ['nullable', 'integer'],
            'congreso_id' => ['nullable', 'integer'],
            'asesor_id' => ['nullable', 'integer'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'con_seguimiento_pendiente' => ['nullable', 'boolean'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $query = Customer::visiblesPara($request->user())
            ->with(['asesor', 'category', 'congress', 'seguimientos'])
            ->when(filled($filtros['buscar'] ?? null), function (Builder $q) use ($filtros) {
                $texto = '%'.$filtros['buscar'].'%';

                $q->where(function (Builder $sub) use ($texto) {
                    $sub->where('nombre', 'like', $texto)
                        ->orWhere('apellido', 'like', $texto)
                        ->orWhereRaw("CONCAT_WS(' ', nombre, apellido) LIKE ?", [$texto])
                        ->orWhere('gmail', 'like', $texto)
                        ->orWhere('telefono', 'like', $texto)
                        ->orWhere('rfc', 'like', $texto)
                        ->orWhere('direccion', 'like', $texto)
                        ->orWhereHas('asesor', fn (Builder $a) => $a->where('name', 'like', $texto));
                });
            })
            ->when(filled($filtros['etapa'] ?? null), fn (Builder $q) => $q->where('etapa', $filtros['etapa']))
            ->when(isset($filtros['activo']), fn (Builder $q) => $q->where('activo', $request->boolean('activo')))
            ->when(isset($filtros['recibe_promocion']), fn (Builder $q) => $q->where('recibe_promocion', $request->boolean('recibe_promocion')))
            ->when(filled($filtros['categoria_id'] ?? null), fn (Builder $q) => $q->where('categoria_id', $filtros['categoria_id']))
            ->when(filled($filtros['congreso_id'] ?? null), fn (Builder $q) => $q->where('congreso_id', $filtros['congreso_id']))
            ->when(filled($filtros['asesor_id'] ?? null), fn (Builder $q) => $q->where('asesor_id', $filtros['asesor_id']))
            ->when(filled($filtros['direccion'] ?? null), fn (Builder $q) => $q->where('direccion', 'like', '%'.$filtros['direccion'].'%'))
            ->when($request->boolean('con_seguimiento_pendiente'), fn (Builder $q) => $q->whereHas('seguimientos', fn (Builder $s) => $s->whereNull('hecho_en')))
            ->when(filled($filtros['desde'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '>=', $filtros['desde']))
            ->when(filled($filtros['hasta'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '<=', $filtros['hasta']));

        $this->ordenar($query, $request, ['id', 'nombre', 'apellido', 'created_at', 'updated_at']);

        return ClienteResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Catálogos del formulario de cliente.
     *
     * Lo que necesita una app para dar de alta o editar un cliente y
     * programarle seguimientos: categorías, congresos, etapas y tipos de
     * seguimiento. `usuarios` (a quién se le puede asignar un seguimiento)
     * solo viene lleno con `clientes.ver_todos`; sin él, cada quien se los
     * programa a sí mismo.
     */
    public function catalogos(Request $request): JsonResponse
    {
        $etiquetas = fn (array $lista) => collect($lista)
            ->map(fn (string $label, string $valor) => ['valor' => $valor, 'label' => $label])
            ->values();

        return response()->json(['data' => [
            'categorias' => CategoriaClienteResource::collection(Category::query()->orderBy('nombre')->get()),
            'congresos' => Congress::query()->latest()->get()->map(fn (Congress $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'fecha_inicio' => $c->fecha_inicio?->toDateString(),
                'fecha_finalizacion' => $c->fecha_finalizacion?->toDateString(),
            ])->values(),
            'etapas' => $etiquetas(Customer::ETAPAS),
            'tipos_seguimiento' => $etiquetas(ClienteSeguimiento::TIPOS),
            'usuarios' => $request->user()->can('clientes.ver_todos')
                ? UsuarioResource::collection(User::orderBy('name')->get())
                : [],
        ]]);
    }

    /**
     * Buscar posible duplicado.
     *
     * ¿Ya hay un cliente con este teléfono o correo? Es la misma revisión
     * que detiene el alta y la edición. El teléfono se compara por sus
     * últimos 10 dígitos (sin importar espacios, guiones o lada), el
     * correo sin distinguir mayúsculas. Al editar manda `ignorar` con el id
     * del propio cliente.
     *
     * Responde `data: null` si no hay choque. Si lo hay, trae el motivo y
     * los datos básicos del cliente que ya existe aunque sea de otro
     * asesor (para saber de quién es); `cliente.url` solo viene si el
     * usuario puede abrir su ficha.
     */
    public function similar(Request $request): JsonResponse
    {
        $request->validate([
            'telefono' => ['nullable', 'string', 'max:30'],
            'gmail' => ['nullable', 'string', 'max:255'],
            'ignorar' => ['nullable', 'integer'],
        ]);

        $aviso = $this->registro->similar(
            $request->input('telefono'),
            $request->input('gmail'),
            $request->integer('ignorar') ?: null
        );

        if (! $aviso) {
            return response()->json(['data' => null]);
        }

        $cliente = $aviso['cliente'];

        return response()->json(['data' => [
            /** telefono | correo */
            'motivo' => $aviso['motivo'],
            'mensaje' => $aviso['mensaje'],
            // Lo mismo que enseña el aviso de la web, con la fecha de alta en Y-m-d.
            'cliente' => array_merge($cliente->resumenParaAviso($request->user()), [
                'alta' => $cliente->created_at?->toDateString(),
            ]),
        ]]);
    }

    /**
     * Registrar cliente.
     *
     * Queda a nombre del usuario del token (`asesor_id`), activo y como
     * `cliente` salvo que se mande `etapa: prospecto`. El RFC se guarda en
     * mayúsculas; si viene `congreso_id`, `como_conocio` se ignora.
     *
     * Si el teléfono o el correo ya son de otro cliente no se guarda:
     * responde 422 con el error en `telefono` o `gmail` (consulta
     * `GET /clientes/similar` para ver de quién es).
     *
     * Opcional: `seguimiento_tipo` + `seguimiento_fecha` (+ `seguimiento_nota`)
     * programan de una vez el primer seguimiento, a nombre del mismo usuario.
     */
    public function store(Request $request): JsonResponse
    {
        $this->registro->normalizar($request);
        $this->detenerSiYaExiste($request);

        $data = $request->validate($this->registro->reglasAlta(), $this->registro->mensajesAlta());

        $cliente = $this->registro->registrar($data, $request->user());

        return (new ClienteResource($this->conDetalle($request, $cliente->refresh())))
            ->additional(['message' => 'Cliente guardado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ver cliente.
     *
     * Ficha completa: asesor, categoría, congreso, todos sus seguimientos
     * (pendientes primero) y, si el token tiene `cotizaciones.ver`, sus
     * cotizaciones (sin `cotizaciones.ver_todas`, solo las del usuario).
     * Un cliente de otro asesor responde 403.
     */
    public function show(Request $request, Customer $cliente): ClienteResource
    {
        $this->asegurarVisible($request, $cliente);

        return new ClienteResource($this->conDetalle($request, $cliente));
    }

    /**
     * Editar cliente.
     *
     * Reemplaza los datos como el formulario de la web: `nombre`,
     * `apellido`, `telefono` y `direccion` son obligatorios en cada envío.
     * `activo` y `recibe_promocion` conservan su valor si no se mandan; la
     * etapa también. Mismo aviso de duplicado (422) que en el alta.
     */
    public function update(Request $request, Customer $cliente): ClienteResource
    {
        $this->asegurarVisible($request, $cliente);

        // La web siempre manda las dos casillas (oculto 0 + casilla 1); por
        // la API, no mandarlas no debe apagarlas sin querer.
        $request->mergeIfMissing([
            'activo' => (bool) $cliente->activo,
            'recibe_promocion' => (bool) $cliente->recibe_promocion,
        ]);

        $this->registro->normalizar($request);
        $this->detenerSiYaExiste($request, $cliente);

        $data = $request->validate($this->registro->reglasEdicion($cliente), $this->registro->mensajesEdicion());

        $this->registro->actualizar($cliente, $data);

        return (new ClienteResource($this->conDetalle($request, $cliente)))
            ->additional(['message' => 'Cliente actualizado correctamente.']);
    }

    /**
     * Convertir prospecto en cliente.
     *
     * Pasa la etapa a `cliente` (el prospecto ya compró o ya se decidió).
     * Pide `clientes.editar`, igual que en la web.
     */
    public function convertir(Request $request, Customer $cliente): ClienteResource
    {
        $this->asegurarVisible($request, $cliente);

        $this->seguimientos->convertir($cliente);

        return (new ClienteResource($this->conDetalle($request, $cliente)))
            ->additional(['message' => trim($cliente->nombre.' '.$cliente->apellido).' ahora es cliente.']);
    }

    /**
     * Un cliente de otro asesor no se abre ni se edita pidiendo su id: si
     * no le aparece en la lista, tampoco existe para él.
     */
    private function asegurarVisible(Request $request, Customer $cliente): void
    {
        abort_unless($cliente->visiblePara($request->user()), 403, 'Este cliente lo registró otro asesor.');
    }

    /** Mismo aviso que la web, pero como error de validación del campo que choca. */
    private function detenerSiYaExiste(Request $request, ?Customer $ignorar = null): void
    {
        $aviso = $this->registro->similar($request->input('telefono'), $request->input('gmail'), $ignorar?->id);

        if ($aviso) {
            throw ValidationException::withMessages([
                $this->registro->campoDelChoque($aviso) => $aviso['mensaje'],
            ]);
        }
    }

    private function conDetalle(Request $request, Customer $cliente): Customer
    {
        $cliente->load(['asesor', 'category', 'congress', 'seguimientos.responsable', 'seguimientos.hechoPor']);

        // Las cotizaciones del cliente son de otro permiso: sin él no se
        // asoman, y sin ver_todas solo las del propio asesor.
        if ($request->user()->can('cotizaciones.ver')) {
            $cliente->load(['cotizaciones' => fn ($q) => $q->visiblesPara($request->user())->latest()]);
        }

        return $cliente;
    }
}
