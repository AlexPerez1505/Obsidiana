<?php

namespace App\Http\Controllers\Api\V1\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Ventas\ComisionFilaResource;
use App\Http\Resources\Api\V1\Ventas\ComisionPagoResource;
use App\Models\ComisionPago;
use App\Models\User;
use App\Services\Ventas\Comisiones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Comisiones por mes: quién vendió más, cuánto se cobró de sus ventas,
 * cuánto le toca según su porcentaje y cuánto ya se le pagó. Ver pide
 * `comisiones.ver`; definir porcentajes y registrar pagos, además
 * `comisiones.gestionar`.
 *
 * @tags Comisiones
 */
class ComisionController extends Controller
{
    public function __construct(private readonly Comisiones $comisiones)
    {
    }

    /**
     * Comisiones del mes.
     *
     * Una fila por asesor con movimiento en el mes (o con porcentaje
     * definido): ventas, vendido, cobrado, porcentaje, comisión, pagado y
     * pendiente. `periodo` es el mes "2026-09" (por defecto el actual) y
     * `base` si la comisión se calcula sobre lo cobrado (por defecto) o lo
     * vendido. Trae también el resumen del mes y los pagos registrados.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $periodo = Comisiones::periodo($request->query('periodo'));
        $base = Comisiones::base($request->query('base'));

        [$inicio, $fin] = Comisiones::limites($periodo);

        $filas = $this->comisiones->filasDelPeriodo($inicio, $fin, $periodo, $base);

        return ComisionFilaResource::collection($filas)->additional([
            'periodo' => $periodo,
            'periodo_texto' => ucfirst($inicio->locale('es')->isoFormat('MMMM YYYY')),
            'anterior' => (clone $inicio)->subMonth()->format('Y-m'),
            'siguiente' => (clone $inicio)->addMonth()->format('Y-m'),
            'base' => $base,
            'bases' => collect(Comisiones::BASES)->map(fn ($nombre, $clave) => ['clave' => $clave, 'nombre' => $nombre])->values(),
            'resumen' => $this->comisiones->resumen($filas),
            'pagos' => ComisionPagoResource::collection($this->comisiones->pagosDelPeriodo($periodo)),
            'puede_gestionar' => $request->user()->can('comisiones.gestionar'),
        ]);
    }

    /**
     * Porcentaje de comisión de un asesor.
     *
     * Define o cambia el porcentaje (0 a 100). Mandar `porcentaje` en null
     * se lo quita.
     */
    public function porcentaje(Request $request, User $asesor): JsonResponse
    {
        $data = $request->validate(Comisiones::REGLAS_PORCENTAJE);

        $this->comisiones->definirPorcentaje($asesor, $data['porcentaje'] ?? null);

        return response()->json([
            'message' => "Porcentaje de {$asesor->name} actualizado.",
            'data' => [
                'user_id' => $asesor->id,
                'name' => $asesor->name,
                'porcentaje_comision' => $asesor->porcentaje_comision !== null ? (float) $asesor->porcentaje_comision : null,
            ],
        ]);
    }

    /**
     * Registrar un pago de comisión.
     *
     * Lo que se le pagó a un asesor por un mes (`periodo` "2026-09") y con
     * qué base se calculó.
     */
    public function pagar(Request $request): JsonResponse
    {
        $data = $request->validate(Comisiones::reglasPago(), Comisiones::MENSAJES_PAGO);

        $pago = $this->comisiones->registrarPago($data, $request->user())->load(['asesor', 'registradoPor']);

        return (new ComisionPagoResource($pago))
            ->additional(['message' => 'Pago de $'.number_format((float) $pago->monto, 2).' registrado a '.$pago->asesor->name.'.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Eliminar un pago de comisión.
     *
     * Para un pago capturado por error (no se editan).
     */
    public function eliminarPago(ComisionPago $pago): JsonResponse
    {
        $pago->delete();

        return response()->json(['message' => 'Pago de comisión eliminado.']);
    }
}
