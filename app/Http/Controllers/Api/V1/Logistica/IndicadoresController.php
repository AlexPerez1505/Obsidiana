<?php

namespace App\Http\Controllers\Api\V1\Logistica;

use App\Http\Controllers\Controller;
use App\Services\Logistica\IndicadoresLogistica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Logística
 */
class IndicadoresController extends Controller
{
    /**
     * Indicadores de logística.
     *
     * Del periodo `desde`–`hasta` (por fecha programada de las rutas; sin
     * fechas, los últimos 30 días; las canceladas no cuentan): entregas,
     * porcentaje de éxito y de puntualidad (contra la ventana de cada
     * parada), km, minutos promedio en cliente, desviaciones, incidencias y
     * gasto de combustible (total y por entrega). Además el desglose por
     * vehículo, por chofer y por día. `kpis.hoy` son las entregas de hoy.
     */
    public function index(Request $request, IndicadoresLogistica $indicadores): JsonResponse
    {
        $f = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        [$desde, $hasta] = $indicadores->periodo($f['desde'] ?? null, $f['hasta'] ?? null);
        $r = $indicadores->calcular($desde, $hasta);

        return response()->json(['data' => [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'kpis' => $r['kpis'],
            'por_vehiculo' => $r['porVehiculo'],
            'por_chofer' => $r['porChofer'],
            'por_dia' => collect($r['porDia'])->map(fn ($x, $fecha) => [
                'fecha' => $fecha,
                'entregadas' => $x['ok'],
                'no_entregadas' => $x['no'],
            ])->values(),
        ]]);
    }
}
