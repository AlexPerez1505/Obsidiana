<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\CalculadoraCotizacion;
use App\Services\Ventas\VentaRapida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Venta rápida: mostrador y congresos. Escanear la etiqueta de cada pieza
 * con el celular, ver el precio, elegir cómo pagó y cobrar. La lógica vive
 * en VentaRapida (la usa también la API).
 */
class VentaRapidaController extends Controller
{
    public function __construct(private readonly VentaRapida $ventaRapida)
    {
    }

    public function index(Request $request): View
    {
        $congresos = $this->ventaRapida->congresosVigentes();

        return view('structure.commercial_management.ventas.rapida', [
            'congresos' => $congresos,
            'congresoSugerido' => $this->ventaRapida->congresoSugerido($congresos, (int) $request->user()->id)?->id,
            // En el orden en que se usan en un stand: efectivo primero.
            'metodos' => VentaRapida::metodos(),
            'garantias' => Venta::GARANTIAS,
            'ivaTasa' => CalculadoraCotizacion::IVA_TASA,
        ]);
    }

    /** Qué pieza es la etiqueta escaneada, con su precio. */
    public function pieza(Request $request): JsonResponse
    {
        $data = $request->validate(['codigo' => ['required', 'string', 'max:255']]);

        ['pieza' => $pieza, 'codigo' => $codigo] = $this->ventaRapida->buscarPieza($data['codigo']);

        if (! $pieza) {
            return response()->json(['encontrado' => false, 'codigo' => $codigo, 'mensaje' => "No hay ninguna pieza con la etiqueta o número de serie \"{$codigo}\"."]);
        }

        return response()->json(['encontrado' => true] + $this->ventaRapida->describirPieza($pieza));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(VentaRapida::reglas(), VentaRapida::MENSAJES);

        try {
            [$venta, $cobro] = $this->ventaRapida->cobrar($data, $request->user());
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'folio' => $venta->folio,
            'cliente' => $this->ventaRapida->nombreCliente($venta),
            'total' => (float) $venta->total,
            'metodo' => $cobro->metodoLabel(),
            'ver' => route('commercial.ventas.show', $venta),
            'recibo' => route('commercial.ventas.cobros.recibo', [$venta, $cobro]),
        ]);
    }
}
