<?php

namespace App\Http\Controllers\Api\V1\Ventas;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Ventas\VentaResource;
use App\Models\Congress;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\Venta;
use App\Services\Ventas\DocumentosDeVenta;
use App\Services\Ventas\RegistroDeVentas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Ventas: alta, edición, cancelación y sus PDF. Se registran exactamente
 * igual que en la web (mismo descuento de stock, plan de pagos y orden de
 * salida para almacén).
 *
 * Quien no tiene `ventas.ver_todas` solo ve y toca sus propias ventas; la
 * de otro asesor responde 403.
 *
 * @tags Ventas
 */
class VentaController extends Controller
{
    use ListadoApi;

    /** Lo que se carga en el detalle y en las respuestas de alta/edición. */
    private const DETALLE = [
        'customer', 'seller', 'congreso', 'items', 'pagos.cobros', 'cobros.aplicaciones.pago',
        'fichas', 'cotizacion', 'bitacora.user', 'ordenSalida',
    ];

    public function __construct(
        private readonly RegistroDeVentas $registro,
        private readonly DocumentosDeVenta $documentos,
    ) {
    }

    /**
     * Listar ventas.
     *
     * Las más recientes primero, con cliente, asesor, renglones y saldos.
     * Sin `ventas.ver_todas` solo salen las del propio usuario.
     *
     * Filtros: `buscar` (folio, cliente, nota o producto), `estado`
     * (borrador, confirmada, facturada, cancelada), `modalidad`,
     * `customer_id`, `seller_id`, `congreso_id`, `desde`/`hasta` (fecha de
     * la venta, Y-m-d). Orden: `orden=folio|created_at|total` (con `-` al
     * frente, descendente).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:borrador,confirmada,facturada,cancelada'],
            'modalidad' => ['nullable', 'in:contado,financiamiento'],
            'customer_id' => ['nullable', 'integer'],
            'seller_id' => ['nullable', 'integer'],
            'congreso_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $query = Venta::visiblesPara($request->user())
            ->with(['customer', 'seller', 'items', 'cobros'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $t = '%'.trim((string) $request->query('buscar')).'%';

                $q->where(fn ($w) => $w->where('folio', 'like', $t)
                    ->orWhere('nota_cliente', 'like', $t)
                    ->orWhereHas('customer', fn ($c) => $c->where('nombre', 'like', $t)
                        ->orWhere('apellido', 'like', $t)
                        ->orWhereRaw("CONCAT(nombre, ' ', COALESCE(apellido, '')) like ?", [$t]))
                    ->orWhereHas('items', fn ($i) => $i->where('nombre', 'like', $t)
                        ->orWhere('marca', 'like', $t)
                        ->orWhere('modelo', 'like', $t)));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('modalidad'), fn ($q) => $q->where('modalidad', $request->query('modalidad')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('seller_id'), fn ($q) => $q->where('seller_id', $request->integer('seller_id')))
            ->when($request->filled('congreso_id'), fn ($q) => $q->where('congreso_id', $request->integer('congreso_id')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('hasta')));

        $this->ordenar($query, $request, ['id', 'folio', 'created_at', 'total'], '-id');

        return VentaResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Datos para armar una venta.
     *
     * Lo que necesita el formulario: congresos, plazos de garantía y
     * modalidades. Con `cotizacion_id` trae además la venta ya prellenada
     * desde esa cotización (en la misma forma en que se manda a `POST
     * /ventas`), con el calendario de pagos recorrido para que el primer
     * pago caiga hoy, y los avisos de stock de lo que no alcanza. Con
     * `customer_id` solo prellena el cliente.
     *
     * Los productos, paquetes, clientes y fichas se buscan con sus propios
     * endpoints.
     */
    public function formulario(Request $request): JsonResponse
    {
        $request->validate([
            'cotizacion_id' => ['nullable', 'integer', 'exists:cotizaciones,id'],
            'customer_id' => ['nullable', 'integer', 'exists:clientes,id'],
        ]);

        $origen = null;
        $cliente = null;

        if ($request->filled('cotizacion_id')) {
            $origen = $this->cotizacionVisible($request, $request->integer('cotizacion_id'));
            $cliente = $origen->customer;
        } elseif ($request->filled('customer_id')) {
            $cliente = Customer::findOrFail($request->integer('customer_id'));
        }

        $inicial = $this->registro->inicial($origen, $cliente);

        return response()->json(['data' => [
            'prellenado' => $this->registro->comoPeticion($inicial),
            'cliente' => $inicial['customer'],
            'cotizacion' => $origen ? ['id' => $origen->id, 'folio' => $origen->folio, 'estado' => $origen->estado] : null,
            'avisos_stock' => $origen ? $this->registro->avisosStock($origen->items) : [],
            'congresos' => Congress::orderBy('nombre')->get()->map(fn (Congress $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'fecha_inicio' => $c->fecha_inicio?->toDateString(),
                'fecha_finalizacion' => $c->fecha_finalizacion?->toDateString(),
            ])->values(),
            'garantias' => Venta::GARANTIAS,
            'modalidades' => [
                ['clave' => 'contado', 'nombre' => 'Contado'],
                ['clave' => 'financiamiento', 'nombre' => 'Financiamiento'],
            ],
        ]]);
    }

    /**
     * Registrar una venta.
     *
     * Renglones (`items`) y calendario (`pagos`, solo en financiamiento) van
     * como arreglos en JSON. Al guardar se descuenta el stock de cada
     * producto y de cada producto de un paquete (FIFO, o las piezas
     * elegidas en `items.*.seriales`), se arma el plan de pagos y nace la
     * orden de salida para almacén. La venta queda a nombre de quien hace
     * la petición.
     *
     * Con `cotizacion_id` la venta nace de esa cotización: lo que no se
     * mande se toma de ella (igual que el formulario prellenado de la web)
     * y la cotización queda marcada como convertida.
     */
    public function store(Request $request): JsonResponse
    {
        $cotizacion = $this->prellenarDesdeCotizacion($request);

        $data = $request->validate(RegistroDeVentas::reglas() + [
            'cotizacion_id' => ['nullable', 'integer', 'exists:cotizaciones,id'],
        ]);

        $venta = $this->registro->crear($data, $cotizacion?->id);

        return (new VentaResource($venta->refresh()->load(self::DETALLE)))
            ->additional(['message' => "Venta {$venta->folio} registrada correctamente."])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ver una venta.
     *
     * Con cliente, asesor, renglones, plan de pagos (con lo cobrado de cada
     * parcialidad), cobros, fichas, cotización de origen, orden de salida e
     * historial de cambios.
     */
    public function show(Venta $venta): VentaResource
    {
        return new VentaResource($venta->load(self::DETALLE));
    }

    /**
     * Editar una venta.
     *
     * Se manda la venta completa, igual que al registrarla. Los renglones se
     * rehacen y el stock se recalcula. Si alguna parcialidad ya tiene
     * cobros, el calendario se conserva (modalidad, meses y `pagos` se
     * ignoran) y la diferencia se reparte entre las parcialidades sin
     * cobrar. El total nunca puede quedar por debajo de lo ya cobrado (422).
     *
     * Una venta cancelada, o cuyo equipo ya salió del almacén con firma de
     * entrega, no se edita (409).
     */
    public function update(Request $request, Venta $venta): VentaResource
    {
        $this->registro->asegurarEditable($venta);

        $data = $request->validate(RegistroDeVentas::reglas());

        $mensaje = $this->registro->actualizar($venta, $data);

        return (new VentaResource($venta->refresh()->load(self::DETALLE)))
            ->additional(['message' => $mensaje]);
    }

    /**
     * Cancelar una venta.
     *
     * No se borra: queda cancelada con su motivo, el equipo regresa al
     * inventario, la orden de salida se cancela y los cobros se conservan
     * como historial. Pide el PIN de aprobación del usuario (o su
     * contraseña si no tiene PIN). Si ya salió el equipo, 409.
     */
    public function cancelar(Request $request, Venta $venta): VentaResource
    {
        $this->registro->asegurarCancelable($venta);

        $data = $request->validate(RegistroDeVentas::REGLAS_CANCELAR, RegistroDeVentas::MENSAJES_CANCELAR);

        $mensaje = $this->registro->cancelar($venta, $data['motivo'], $data['password'], $request->user());

        return (new VentaResource($venta->refresh()->load(self::DETALLE)))
            ->additional(['message' => $mensaje]);
    }

    /**
     * Eliminar una venta.
     *
     * Solo si no tiene ningún cobro y su equipo no ha salido (si no, 409:
     * hay que cancelarla). Regresa el stock, cancela la orden de salida y,
     * si venía de una cotización, la borra también.
     */
    public function destroy(Venta $venta): JsonResponse
    {
        return response()->json(['message' => $this->registro->eliminar($venta)]);
    }

    /**
     * PDF de la venta.
     *
     * El mismo archivo que se baja en la web: la venta, el contrato (si es
     * a plazos), la carta garantía (si tiene) y las fichas técnicas.
     */
    public function pdf(Venta $venta): Response
    {
        return $this->documentos->venta($venta);
    }

    /**
     * PDF del contrato.
     *
     * Contrato de compraventa a plazos. Una venta de contado no tiene (404).
     */
    public function contrato(Venta $venta): Response
    {
        // HttpException y no abort(404): en /api abort(404) pierde el mensaje.
        if (! $venta->requiereContrato()) {
            throw new HttpException(404, 'El contrato solo aplica a ventas a plazos.');
        }

        return $this->documentos->contrato($venta);
    }

    /**
     * PDF de la carta garantía.
     *
     * Una venta registrada sin garantía no tiene carta (404).
     */
    public function garantia(Venta $venta): Response
    {
        if (! $venta->tieneGarantia()) {
            throw new HttpException(404, 'Esta venta se registró sin garantía: no hay carta garantía que generar.');
        }

        return $this->documentos->garantia($venta);
    }

    /**
     * Con cotizacion_id, lo que la petición no trae se toma de la
     * cotización (el mismo prellenado del formulario web). Se valida aquí,
     * antes que el resto, porque de ella sale lo demás.
     */
    private function prellenarDesdeCotizacion(Request $request): ?Cotizacion
    {
        if (! $request->filled('cotizacion_id')) {
            return null;
        }

        $request->validate(['cotizacion_id' => ['integer', 'exists:cotizaciones,id']]);
        $cotizacion = $this->cotizacionVisible($request, $request->integer('cotizacion_id'));

        $prellenado = $this->registro->comoPeticion($this->registro->inicial($cotizacion, $cotizacion->customer));
        $request->merge(array_diff_key($prellenado, $request->all()));

        return $cotizacion;
    }

    /** Solo se convierte una cotización que el usuario puede ver. */
    private function cotizacionVisible(Request $request, int $id): Cotizacion
    {
        $cotizacion = Cotizacion::with(['customer', 'items.producto', 'pagos', 'fichas'])->findOrFail($id);

        abort_unless($cotizacion->visiblePara($request->user()), 403, 'Esa cotización la hizo otro asesor.');

        return $cotizacion;
    }
}
