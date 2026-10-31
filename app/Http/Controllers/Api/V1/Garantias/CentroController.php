<?php

namespace App\Http\Controllers\Api\V1\Garantias;

use App\Http\Controllers\Controller;
use App\Models\Garantia;
use App\Models\GarantiaReclamacion;
use App\Services\Garantias\IndicadoresDeGarantias;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Centro de garantías: métricas del tablero, atención pendiente, próximas
 * a vencer, reclamaciones por mes y reportes del período.
 *
 * @tags Garantías
 */
class CentroController extends Controller
{
    use FormatoGarantias;

    public function __construct(private IndicadoresDeGarantias $indicadores) {}

    /**
     * Resumen del centro de garantías.
     */
    public function centro(): JsonResponse
    {
        return response()->json([
            'resumen' => $this->indicadores->resumen(),
            'por_mes' => $this->indicadores->porMes(6)->map(fn ($m) => [
                'mes' => $m['mes']->locale('es')->isoFormat('MMM'),
                'total' => $m['total'],
            ])->values(),
            'atencion' => GarantiaReclamacion::abiertas()
                ->with(['garantia', 'customer', 'responsable'])
                ->orderByDesc('es_seguridad')
                ->orderByRaw("FIELD(prioridad, 'critica', 'alta', 'media', 'baja')")
                ->orderBy('limite_en')
                ->limit(8)->get()
                ->map(fn (GarantiaReclamacion $r) => $this->reclamacionJson($r))
                ->values(),
            'proximas' => Garantia::porVencer()->with('customer')->orderBy('vence')->limit(6)->get()
                ->map(fn (Garantia $g) => $this->garantiaJson($g))
                ->values(),
        ]);
    }

    /**
     * Reportes de garantías. `periodo`: 3, 6, 12 meses o "todo".
     */
    public function reportes(Request $request): JsonResponse
    {
        $periodo = $request->get('periodo', '12');
        $desde = $periodo === 'todo' ? null : now()->subMonths((int) $periodo)->startOfDay();

        return response()->json([
            'periodo' => $periodo,
            'kpi' => $this->indicadores->indicadores($desde),
            'por_mes' => $this->indicadores->porMes(12)->map(fn ($m) => [
                'mes' => $m['mes']->locale('es')->isoFormat('MMM'),
                'total' => $m['total'],
            ])->values(),
            'modelos' => $this->indicadores->rankingModelos()->values(),
            'proveedores' => $this->indicadores->rankingProveedores()->values(),
        ]);
    }
}
