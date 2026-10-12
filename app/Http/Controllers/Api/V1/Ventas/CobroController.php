<?php

namespace App\Http\Controllers\Api\V1\Ventas;

use App\Http\Controllers\Api\V1\Ventas\Concerns\DeLaMismaVenta;
use App\Http\Controllers\CobroController as CobroWeb;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Ventas\CobroResource;
use App\Http\Resources\Api\V1\Ventas\VentaPagoResource;
use App\Models\Cobro;
use App\Models\Venta;
use App\Services\CalendarioPagos;
use App\Services\Ventas\CobrosDeVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Cobros de una venta: lo que ya pagó el cliente, con su evidencia y su
 * recibo. Un cobro no se edita: si se capturó mal, se cancela y se
 * registra de nuevo, para que el recibo entregado cuadre con el sistema.
 *
 * Sigue el alcance de la venta: sin `ventas.ver_todas`, solo la cobranza
 * de las ventas propias.
 *
 * @tags Cobranza
 */
class CobroController extends Controller
{
    use DeLaMismaVenta;

    public function __construct(
        private readonly CobrosDeVenta $cobros,
        private readonly CalendarioPagos $calendario,
    ) {
    }

    /**
     * Cobranza de una venta.
     *
     * Los cobros (con cómo se repartió cada uno entre las parcialidades y
     * sus evidencias), el plan de pagos con lo cobrado y lo que falta de
     * cada parcialidad, y el resumen: exigible, cobrado, saldo y el
     * excedente que todavía no cae en ninguna parcialidad.
     */
    public function index(Venta $venta): AnonymousResourceCollection
    {
        $venta->load(['pagos.cobros', 'cobros.evidencias', 'cobros.aplicaciones.pago', 'cobros.registradoPor']);

        return CobroResource::collection($venta->cobros)->additional([
            'resumen' => [
                'venta_id' => $venta->id,
                'folio' => $venta->folio,
                'estado' => $venta->estado,
                'monto_exigible' => $venta->montoExigible(),
                'total_cobrado' => $venta->totalCobrado(),
                'saldo' => $venta->saldo(),
                'avance' => $venta->avance(),
                'estado_pago' => $venta->estadoPago(),
                'excedente_pendiente' => $this->calendario->excedentePendiente($venta),
            ],
            'parcialidades' => VentaPagoResource::collection($venta->pagos),
            'metodos' => collect(Cobro::METODOS)->map(fn ($nombre, $clave) => ['clave' => $clave, 'nombre' => $nombre])->values(),
        ]);
    }

    /**
     * Ver un cobro.
     *
     * Con sus evidencias, a qué parcialidades se aplicó y quién lo registró.
     */
    public function show(Venta $venta, Cobro $cobro): CobroResource
    {
        $this->asegurarDeLaVenta($venta, $cobro);

        return new CobroResource($cobro->load(['evidencias', 'aplicaciones.pago', 'registradoPor']));
    }

    /**
     * Registrar un cobro.
     *
     * Multipart si lleva evidencias (`evidencias[]`: hasta 5 imágenes o
     * PDF). El cobro queda como un solo recibo y se aplica en orden a las
     * parcialidades pendientes, empezando por `venta_pago_id` si se manda.
     * No se puede cobrar más que el saldo (422) ni cobrarle a una venta
     * cancelada (409). La respuesta dice cómo se repartió (`aplicacion`).
     */
    public function store(Request $request, Venta $venta): JsonResponse
    {
        $this->cobros->asegurarCobrable($venta);

        $data = $request->validate(CobrosDeVenta::reglasCobro($venta), CobrosDeVenta::MENSAJES_COBRO);

        $r = $this->cobros->registrar($venta, $data, (array) $request->file('evidencias', []));

        return (new CobroResource($r['cobro']->load(['evidencias', 'aplicaciones.pago', 'registradoPor'])))
            ->additional([
                'message' => $r['mensaje'],
                'aplicacion' => [
                    'aplicado' => (float) $r['aplicacion']['aplicado'],
                    'sin_aplicar' => (float) $r['aplicacion']['sin_aplicar'],
                    'lineas' => collect($r['aplicacion']['lineas'])->map(fn ($l) => [
                        'venta_pago_id' => $l['pago']->id,
                        'nombre' => $l['pago']->nombre,
                        'monto' => (float) $l['monto'],
                        'liquida' => (bool) $l['liquida'],
                        'resta' => (float) $l['resta'],
                    ])->values(),
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Cancelar un cobro.
     *
     * Lo borra junto con sus evidencias y lo deja anotado en el historial
     * de la venta. El saldo vuelve a subir.
     */
    public function destroy(Venta $venta, Cobro $cobro): JsonResponse
    {
        $this->asegurarDeLaVenta($venta, $cobro);

        return response()->json(['message' => $this->cobros->cancelarCobro($venta, $cobro)]);
    }

    /**
     * Recibo en PDF.
     *
     * El mismo recibo que se entrega en la web y en la consulta del cliente.
     */
    public function recibo(Venta $venta, Cobro $cobro): Response
    {
        $this->asegurarDeLaVenta($venta, $cobro);

        return CobroWeb::pdfRecibo($cobro);
    }
}
