<?php

namespace App\Http\Controllers\Api\V1\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Ventas\CobroResource;
use App\Http\Resources\Api\V1\Ventas\VentaResource;
use App\Models\Congress;
use App\Models\Venta;
use App\Services\CalculadoraCotizacion;
use App\Services\Ventas\VentaRapida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Venta rápida de mostrador y congresos: escanear la etiqueta de cada
 * pieza, ver su precio, elegir cómo pagó y cobrar. Sale una venta normal
 * (folio, cobro y salida de inventario) ya entregada, a nombre del cliente
 * elegido o de "Público en general".
 *
 * @tags Venta rápida
 */
class VentaRapidaController extends Controller
{
    public function __construct(private readonly VentaRapida $ventaRapida)
    {
    }

    /**
     * Datos para la venta rápida.
     *
     * Congresos que no han terminado, el que conviene preseleccionar (uno
     * activo hoy donde esté anotado el usuario), las formas de pago del
     * stand, los plazos de garantía y la tasa de IVA.
     */
    public function formulario(Request $request): JsonResponse
    {
        $congresos = $this->ventaRapida->congresosVigentes();

        return response()->json(['data' => [
            'congresos' => $congresos->map(fn (Congress $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'fecha_inicio' => $c->fecha_inicio?->toDateString(),
                'fecha_finalizacion' => $c->fecha_finalizacion?->toDateString(),
                // upcoming | active
                'estado' => $c->estado(),
            ])->values(),
            'congreso_sugerido_id' => $this->ventaRapida->congresoSugerido($congresos, (int) $request->user()->id)?->id,
            'metodos' => collect(VentaRapida::metodos())
                ->map(fn ($nombre, $clave) => ['clave' => $clave, 'nombre' => $nombre])
                ->values(),
            'garantias' => Venta::GARANTIAS,
            'iva_tasa' => CalculadoraCotizacion::IVA_TASA,
        ]]);
    }

    /**
     * Buscar una pieza por su etiqueta.
     *
     * `codigo` es lo que leyó el escáner: la etiqueta interna (MB-000147),
     * la liga completa del QR o el número de serie del fabricante. Regresa
     * la pieza con su precio y si se puede vender (`vendible`; si no,
     * `motivo` dice por qué). 404 si no hay ninguna pieza con ese código.
     */
    public function pieza(Request $request): JsonResponse
    {
        $data = $request->validate(['codigo' => ['required', 'string', 'max:255']]);

        ['pieza' => $pieza, 'codigo' => $codigo] = $this->ventaRapida->buscarPieza($data['codigo']);

        if (! $pieza) {
            // HttpException y no abort(404): abort pierde el mensaje en /api.
            throw new HttpException(404, "No hay ninguna pieza con la etiqueta o número de serie \"{$codigo}\".");
        }

        $datos = $this->ventaRapida->describirPieza($pieza);
        $datos['foto_url'] = $datos['foto'];
        unset($datos['foto']);

        return response()->json(['data' => $datos]);
    }

    /**
     * Cobrar una venta rápida.
     *
     * Por cada producto: cantidad, precio y, si se escanearon, los
     * `seriales` exactos (si se vende más de lo escaneado, el resto se
     * completa con piezas del mismo producto, primero las de ese
     * congreso). Crea la venta de contado ya confirmada, su cobro, descuenta
     * el stock y cierra la orden de salida como entregada. Si una pieza ya
     * no está disponible o no alcanza el stock, no se crea nada (422).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(VentaRapida::reglas(), VentaRapida::MENSAJES);

        [$venta, $cobro] = $this->ventaRapida->cobrar($data, $request->user());

        $venta->load(['customer', 'seller', 'congreso', 'items', 'cobros', 'ordenSalida']);

        return (new VentaResource($venta))
            ->additional([
                'message' => "Venta {$venta->folio} cobrada y entregada a {$this->ventaRapida->nombreCliente($venta)}.",
                'cobro' => new CobroResource($cobro),
            ])
            ->response()
            ->setStatusCode(201);
    }
}
