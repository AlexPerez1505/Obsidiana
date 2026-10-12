<?php

namespace App\Http\Controllers\Api\V1\Ventas;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Ventas\FacturaResource;
use App\Models\Customer;
use App\Models\Factura;
use App\Models\Venta;
use App\Services\Ventas\BorradoresDeFactura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Borradores de factura (pre-factura interna, sin validez fiscal): los
 * datos del receptor y los montos y renglones de la venta tal como
 * estaban el día que se generó.
 *
 * @tags Facturación
 */
class FacturaController extends Controller
{
    use ListadoApi;

    public function __construct(private readonly BorradoresDeFactura $borradores)
    {
    }

    /**
     * Listar borradores.
     *
     * Los más recientes primero. Filtros: `buscar` (folio, RFC, razón
     * social, cliente o folio de la venta), `estado` (borrador, emitida,
     * cancelada), `customer_id`, `venta_id`, `desde`/`hasta` (Y-m-d).
     * Orden: `orden=folio|created_at|total`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:borrador,emitida,cancelada'],
            'customer_id' => ['nullable', 'integer'],
            'venta_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $query = Factura::with(['customer', 'venta'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $t = '%'.trim((string) $request->query('buscar')).'%';

                $q->where(fn ($w) => $w->where('folio', 'like', $t)
                    ->orWhere('rfc', 'like', $t)
                    ->orWhere('razon_social', 'like', $t)
                    ->orWhereHas('venta', fn ($v) => $v->where('folio', 'like', $t))
                    ->orWhereHas('customer', fn ($c) => $c->where('nombre', 'like', $t)
                        ->orWhere('apellido', 'like', $t)
                        ->orWhereRaw("CONCAT(nombre, ' ', COALESCE(apellido, '')) like ?", [$t])));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('venta_id'), fn ($q) => $q->where('venta_id', $request->integer('venta_id')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('hasta')));

        $this->ordenar($query, $request, ['id', 'folio', 'created_at', 'total'], '-id');

        return FacturaResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Datos para un borrador nuevo.
     *
     * Los catálogos del SAT que ofrece el formulario (uso de CFDI, forma y
     * método de pago) y, con `venta_id` o `customer_id`, el borrador
     * prellenado: cliente, RFC y razón social del cliente, y los renglones
     * y montos que se copiarán de la venta.
     */
    public function formulario(Request $request): JsonResponse
    {
        $request->validate([
            'venta_id' => ['nullable', 'integer', 'exists:ventas,id'],
            'customer_id' => ['nullable', 'integer', 'exists:clientes,id'],
        ]);

        $venta = null;
        $cliente = null;

        if ($request->filled('venta_id')) {
            $venta = Venta::with(['customer', 'items'])->find($request->integer('venta_id'));
            $cliente = $venta?->customer;
        } elseif ($request->filled('customer_id')) {
            $cliente = Customer::find($request->integer('customer_id'));
        }

        $lista = fn (array $catalogo) => collect($catalogo)
            ->map(fn ($texto, $clave) => ['clave' => (string) $clave, 'nombre' => $texto])
            ->values();

        return response()->json(['data' => [
            'prellenado' => [
                'venta_id' => $venta?->id,
                'customer_id' => $cliente?->id,
                'rfc' => $cliente?->rfc,
                'razon_social' => $cliente ? trim(($cliente->nombre ?? '').' '.($cliente->apellido ?? '')) : null,
            ],
            'venta' => $venta ? [
                'id' => $venta->id,
                'folio' => $venta->folio,
                'subtotal' => (float) $venta->subtotal,
                'descuento_monto' => (float) $venta->descuento_monto,
                'envio' => (float) $venta->envio,
                'aplica_iva' => (bool) $venta->aplica_iva,
                'iva_monto' => (float) $venta->iva_monto,
                'total' => (float) $venta->total,
                'items' => $venta->items->map(fn ($i) => [
                    'nombre' => $i->nombre,
                    'modelo' => $i->modelo,
                    'marca' => $i->marca,
                    'cantidad' => (int) $i->cantidad,
                    'no_series' => $i->no_series,
                    'precio_unitario' => (float) $i->precio_unitario,
                    'sobreprecio' => (float) $i->sobreprecio,
                    'es_regalo' => (bool) $i->es_regalo,
                ])->values(),
            ] : null,
            'usos_cfdi' => $lista(BorradoresDeFactura::USOS_CFDI),
            'formas_pago' => $lista(BorradoresDeFactura::FORMAS_PAGO),
            'metodos_pago' => $lista(BorradoresDeFactura::METODOS_PAGO),
        ]]);
    }

    /**
     * Generar un borrador.
     *
     * Con `venta_id` copia los montos y renglones de la venta tal como
     * están hoy y la marca como facturada.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(BorradoresDeFactura::reglas());

        $factura = $this->borradores->crear($data);

        return (new FacturaResource($factura->load(['customer', 'venta', 'items'])))
            ->additional(['message' => "Borrador de factura {$factura->folio} generado."])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ver un borrador.
     *
     * Con su cliente, la venta de origen y los renglones.
     */
    public function show(Factura $factura): FacturaResource
    {
        return new FacturaResource($factura->load(['customer', 'venta', 'items']));
    }

    /**
     * Eliminar un borrador.
     */
    public function destroy(Factura $factura): JsonResponse
    {
        $folio = $factura->folio;
        $factura->delete();

        return response()->json(['message' => "Borrador {$folio} eliminado."]);
    }

    /**
     * PDF del borrador.
     *
     * El mismo que se ve en la web.
     */
    public function pdf(Factura $factura): Response
    {
        return $this->borradores->pdf($factura);
    }
}
