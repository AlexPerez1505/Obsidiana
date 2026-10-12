<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Comercial\CotizacionResource;
use App\Models\Congress;
use App\Models\Cotizacion;
use App\Models\Venta;
use App\Services\CalculadoraCotizacion;
use App\Services\Cotizaciones\GuardarCotizacion;
use App\Services\Cotizaciones\PdfCotizacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Cotizaciones de equipo médico. Sin `cotizaciones.ver_todas` cada asesor
 * solo ve, edita, borra y descarga las que él hizo (las de otro dan 403).
 *
 * Los montos y el plan de pagos los calcula SIEMPRE el servidor: de lo que
 * se manda solo cuentan precios, cantidades, sobreprecios, regalos,
 * descuento, envío, IVA, valor a cuenta y, en el plan, nombre, fecha y
 * monto de las parcialidades `bloqueado`.
 *
 * @tags Cotizaciones
 */
class CotizacionController extends Controller
{
    use ListadoApi;

    public function __construct(private readonly GuardarCotizacion $guardar)
    {
    }

    /**
     * Listar cotizaciones.
     *
     * Paginado, de la más reciente a la más vieja. Cada una trae cliente,
     * asesor, sus renglones y `resumen_productos`.
     *
     * Filtros: `buscar` (folio, nombre o apellido del cliente, congreso),
     * `estado`, `modalidad`, `customer_id`, `seller_id`, `congreso_id`,
     * `desde` / `hasta` (fecha de creación).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            /** borrador | enviada | aceptada | rechazada | convertida */
            'estado' => ['nullable', 'in:borrador,enviada,aceptada,rechazada,convertida'],
            /** contado | financiamiento */
            'modalidad' => ['nullable', 'in:contado,financiamiento'],
            'customer_id' => ['nullable', 'integer'],
            'seller_id' => ['nullable', 'integer'],
            'congreso_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $query = Cotizacion::visiblesPara($request->user())
            ->with(['customer', 'seller', 'items'])
            ->when(filled($filtros['buscar'] ?? null), function (Builder $q) use ($filtros) {
                $texto = '%'.$filtros['buscar'].'%';

                $q->where(function (Builder $sub) use ($texto) {
                    $sub->where('folio', 'like', $texto)
                        ->orWhere('lugar_propuesta', 'like', $texto)
                        ->orWhereHas('customer', fn (Builder $c) => $c
                            ->where('nombre', 'like', $texto)
                            ->orWhere('apellido', 'like', $texto)
                            ->orWhereRaw("CONCAT_WS(' ', nombre, apellido) LIKE ?", [$texto]));
                });
            })
            ->when(filled($filtros['estado'] ?? null), fn (Builder $q) => $q->where('estado', $filtros['estado']))
            ->when(filled($filtros['modalidad'] ?? null), fn (Builder $q) => $q->where('modalidad', $filtros['modalidad']))
            ->when(filled($filtros['customer_id'] ?? null), fn (Builder $q) => $q->where('customer_id', $filtros['customer_id']))
            ->when(filled($filtros['seller_id'] ?? null), fn (Builder $q) => $q->where('seller_id', $filtros['seller_id']))
            ->when(filled($filtros['congreso_id'] ?? null), fn (Builder $q) => $q->where('congreso_id', $filtros['congreso_id']))
            ->when(filled($filtros['desde'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '>=', $filtros['desde']))
            ->when(filled($filtros['hasta'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '<=', $filtros['hasta']));

        $this->ordenar($query, $request, ['id', 'folio', 'total', 'created_at', 'updated_at']);

        return CotizacionResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Catálogos del cotizador.
     *
     * Congresos (para `congreso_id`), garantías permitidas en meses, y los
     * valores posibles de modalidad, tipo de descuento y estado, más la
     * tasa de IVA que usa el cálculo.
     */
    public function catalogos(): JsonResponse
    {
        return response()->json(['data' => [
            'congresos' => Congress::orderBy('nombre')->get()->map(fn (Congress $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'fecha_inicio' => $c->fecha_inicio?->toDateString(),
                'fecha_finalizacion' => $c->fecha_finalizacion?->toDateString(),
            ])->values(),
            'garantias_meses' => Venta::GARANTIAS,
            'garantia_por_defecto' => 6,
            'modalidades' => ['contado', 'financiamiento'],
            'descuento_tipos' => ['porcentaje', 'monto'],
            'tipos_item' => ['equipo', 'paquete', 'producto'],
            'estados' => collect(['borrador', 'enviada', 'aceptada', 'rechazada', 'convertida'])
                ->map(fn (string $e) => ['valor' => $e, 'label' => (new Cotizacion(['estado' => $e]))->estadoLabel()])
                ->values(),
            'iva_tasa' => CalculadoraCotizacion::IVA_TASA,
        ]]);
    }

    /**
     * Calcular montos sin guardar.
     *
     * Con los mismos datos que se mandarían al guardar, regresa el
     * desglose (subtotal, descuento, base, IVA, total, total del contrato)
     * y, si es a financiamiento, el plan de pagos tal como quedaría. Sirve
     * para enseñar el total en la app antes de guardar; no hace falta
     * cliente. Pide `cotizaciones.crear` o `cotizaciones.editar`.
     */
    public function calcular(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['cotizaciones.crear', 'cotizaciones.editar']), 403);

        $data = $request->validate($this->guardar->reglasCalculo());

        $desglose = $this->guardar->desglose($data);

        return response()->json(['data' => $desglose + [
            'pagos' => $this->guardar->plan($data, (float) $desglose['total_contrato']),
        ]]);
    }

    /**
     * Crear cotización.
     *
     * Recibe JSON: datos de cabecera, `items` (al menos uno), `pagos`
     * (opcional, solo a financiamiento) y `fichas` (ids de fichas técnicas
     * que se anexan al PDF). Se le asigna el siguiente folio COT-AAAA-#### y
     * queda a nombre del usuario del token.
     *
     * Cada renglón guarda una copia de nombre, marca, modelo, imagen y
     * precio (el buscador `GET /cotizaciones/buscar/productos` los da
     * listos; `imagen_url` va como `imagen`). En financiamiento, si no se
     * mandan `pagos`, se generan `num_meses + 1` parcialidades iguales desde
     * hoy; las que vengan con `bloqueado: true` conservan su monto y el resto
     * se reparte entre las demás.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->guardar->reglas());

        $cotizacion = $this->guardar->crear($data, $request->user());

        return (new CotizacionResource($this->conDetalle($cotizacion->refresh())))
            ->additional(['message' => "Cotización {$cotizacion->folio} guardada correctamente."])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ver cotización.
     *
     * Con cliente, asesor, congreso, renglones, plan de pagos y fichas.
     */
    public function show(Cotizacion $cotizacion): CotizacionResource
    {
        return new CotizacionResource($this->conDetalle($cotizacion));
    }

    /**
     * Editar cotización.
     *
     * Mismo cuerpo que al crear, completo: renglones, plan y fichas se
     * reemplazan por lo que se mande (no mandar `fichas` las quita). El
     * folio y el asesor no cambian.
     */
    public function update(Request $request, Cotizacion $cotizacion): CotizacionResource
    {
        $data = $request->validate($this->guardar->reglas());

        $this->guardar->actualizar($cotizacion, $data);

        return (new CotizacionResource($this->conDetalle($cotizacion->refresh())))
            ->additional(['message' => "Cotización {$cotizacion->folio} actualizada."]);
    }

    /**
     * Eliminar cotización.
     *
     * Se borra con sus renglones, plan y fichas adjuntas. Si ya se había
     * convertido en venta, la venta se queda (solo pierde la referencia).
     */
    public function destroy(Cotizacion $cotizacion): JsonResponse
    {
        $folio = $cotizacion->folio;
        $cotizacion->delete();

        return response()->json(['message' => "Cotización {$folio} eliminada."]);
    }

    /**
     * Descargar PDF.
     *
     * El mismo PDF de la web (application/pdf): la cotización y, al final,
     * las fichas técnicas adjuntas.
     */
    public function pdf(Cotizacion $cotizacion, PdfCotizacion $pdf): Response
    {
        return response($pdf->generar($cotizacion), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->nombreArchivo($cotizacion).'"',
        ]);
    }

    private function conDetalle(Cotizacion $cotizacion): Cotizacion
    {
        return $cotizacion->load(['customer', 'seller', 'congreso', 'items', 'pagos', 'fichas']);
    }
}
