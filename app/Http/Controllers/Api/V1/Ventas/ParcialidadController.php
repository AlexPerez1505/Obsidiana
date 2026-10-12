<?php

namespace App\Http\Controllers\Api\V1\Ventas;

use App\Http\Controllers\Api\V1\Ventas\Concerns\DeLaMismaVenta;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Ventas\VentaPagoResource;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Services\Ventas\CobrosDeVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * El plan de pagos de una venta y sus ajustes. Regla que nunca se rompe:
 * una parcialidad con dinero cobrado encima no se mueve, no se borra ni
 * baja de lo ya cobrado, para que el recibo del cliente siga cuadrando.
 *
 * @tags Cobranza
 */
class ParcialidadController extends Controller
{
    use DeLaMismaVenta;

    public function __construct(private readonly CobrosDeVenta $cobros)
    {
    }

    /**
     * Plan de pagos.
     *
     * Las parcialidades en orden, con lo cobrado, lo que falta y su estado
     * (pendiente, vencido, parcial, pagado).
     */
    public function index(Venta $venta): AnonymousResourceCollection
    {
        return $this->plan($venta);
    }

    /**
     * Agregar una parcialidad.
     *
     * Se agrega al final del plan.
     */
    public function store(Request $request, Venta $venta): JsonResponse
    {
        $data = $request->validate(CobrosDeVenta::REGLAS_PARCIALIDAD_NUEVA);

        $pago = $this->cobros->agregarParcialidad($venta, $data);

        return (new VentaPagoResource($pago->load('cobros')))
            ->additional(['message' => 'Parcialidad agregada.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Cambiar fecha o monto de una parcialidad.
     *
     * El monto no puede quedar por debajo de lo que ya se le cobró (422).
     */
    public function update(Request $request, Venta $venta, VentaPago $parcialidad): VentaPagoResource
    {
        $this->asegurarDeLaVenta($venta, $parcialidad);

        $data = $request->validate(CobrosDeVenta::REGLAS_PARCIALIDAD);

        $mensaje = $this->cobros->actualizarParcialidad($venta, $parcialidad, $data);

        return (new VentaPagoResource($parcialidad->refresh()->load('cobros')))
            ->additional(['message' => $mensaje]);
    }

    /**
     * Eliminar una parcialidad.
     *
     * Solo si no tiene cobros (409): primero hay que cancelar sus cobros.
     */
    public function destroy(Venta $venta, VentaPago $parcialidad): JsonResponse
    {
        $this->asegurarDeLaVenta($venta, $parcialidad);

        return response()->json(['message' => $this->cobros->eliminarParcialidad($venta, $parcialidad)]);
    }

    /**
     * Recorrer el calendario.
     *
     * El primer pago pendiente pasa a `nueva_fecha` y los demás se mueven
     * los mismos días, conservando los intervalos. Lo ya cobrado no se mueve.
     */
    public function recorrer(Request $request, Venta $venta): AnonymousResourceCollection
    {
        $data = $request->validate([
            'nueva_fecha' => ['required', 'date'],
        ]);

        $mensaje = $this->cobros->recorrer($venta, Carbon::parse($data['nueva_fecha']));

        return $this->plan($venta)->additional(['message' => $mensaje]);
    }

    /**
     * Rebalancear el plan.
     *
     * Reparte la diferencia entre el total de la venta y la suma del plan
     * entre las parcialidades sin cobrar. Si todas tienen cobros no hay
     * dónde repartirla (409): agrega una parcialidad nueva.
     */
    public function rebalancear(Venta $venta): AnonymousResourceCollection
    {
        $mensaje = $this->cobros->rebalancear($venta);

        return $this->plan($venta)->additional(['message' => $mensaje]);
    }

    /**
     * Absorber excedente.
     *
     * El dinero cobrado que ninguna parcialidad ha recibido (abonos sueltos
     * o lo que se pasó de lo que le tocaba a un pago) se aplica en orden a
     * las parcialidades pendientes, sin partir los cobros.
     */
    public function absorberExcedente(Venta $venta): AnonymousResourceCollection
    {
        $mensaje = $this->cobros->absorberExcedente($venta);

        return $this->plan($venta)->additional(['message' => $mensaje]);
    }

    private function plan(Venta $venta): AnonymousResourceCollection
    {
        return VentaPagoResource::collection($venta->pagos()->with('cobros')->get());
    }
}
