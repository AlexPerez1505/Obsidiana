<?php

namespace App\Http\Controllers\Garantias;

use App\Http\Controllers\Controller;
use App\Models\Garantia;
use App\Models\GarantiaReclamacion;
use App\Services\Garantias\IndicadoresDeGarantias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Centro de garantías: indicadores del mes, lo que requiere atención y la
 * búsqueda por número de serie.
 */
class TableroController extends Controller
{
    public function __construct(private IndicadoresDeGarantias $indicadores) {}

    public function index(): View
    {
        return view('structure.garantias.tablero', [
            'resumen' => $this->indicadores->resumen(),
            'porMes' => $this->indicadores->porMes(6),
            'atencion' => GarantiaReclamacion::abiertas()
                ->with(['garantia', 'customer', 'responsable'])
                ->orderByDesc('es_seguridad')
                ->orderByRaw("FIELD(prioridad, 'critica', 'alta', 'media', 'baja')")
                ->orderBy('limite_en')
                ->limit(8)->get(),
            'proximas' => Garantia::porVencer()->with('customer')->orderBy('vence')->limit(6)->get(),
        ]);
    }

    public function reportes(Request $request): View
    {
        $periodo = $request->get('periodo', '12');
        $desde = $periodo === 'todo' ? null : now()->subMonths((int) $periodo)->startOfDay();

        return view('structure.garantias.reportes', [
            'periodo' => $periodo,
            'kpi' => $this->indicadores->indicadores($desde),
            'porMes' => $this->indicadores->porMes(12),
            'modelos' => $this->indicadores->rankingModelos(),
            'proveedores' => $this->indicadores->rankingProveedores(),
        ]);
    }

    /**
     * Buscar por serie: si hay un solo expediente se abre directo, si hay
     * varios (la serie pasó por más de una venta) se muestra su historial.
     */
    public function buscar(Request $request): RedirectResponse
    {
        $q = trim((string) $request->get('q'));

        if ($q === '') {
            return redirect()->route('garantias.index');
        }

        $exactas = Garantia::where('no_serie', $q)->orWhere('codigo_interno', $q)->orWhere('folio', $q)->get();

        if ($exactas->count() === 1) {
            return redirect()->route('garantias.show', $exactas->first());
        }
        if ($exactas->count() > 1) {
            return redirect()->route('garantias.serie', ['serie' => $exactas->first()->no_serie ?: $q]);
        }

        $rec = GarantiaReclamacion::where('folio', $q)->first();
        if ($rec) {
            return redirect()->route('garantias.reclamaciones.show', $rec);
        }

        return redirect()->route('garantias.index', ['q' => $q]);
    }

    /** Historial completo de una serie: ventas, garantías, fallas y costos. */
    public function serie(string $serie): View
    {
        $garantias = Garantia::with(['customer', 'venta', 'reclamaciones.costos', 'eventos.usuario'])
            ->where(fn ($q) => $q->where('no_serie', $serie)->orWhere('codigo_interno', $serie))
            ->orderBy('inicio')
            ->get();

        // Sustituciones donde esta serie entró o salió.
        $sustituciones = \App\Models\GarantiaSustitucion::with('reclamacion')
            ->where(fn ($q) => $q->where('no_serie_entregado', $serie)->orWhere('no_serie_anterior', $serie))
            ->get();

        $reclamaciones = GarantiaReclamacion::with(['garantia', 'costos'])
            ->where('no_serie', $serie)
            ->orWhereIn('garantia_id', $garantias->pluck('id'))
            ->latest('recibida_en')->get();

        return view('structure.garantias.serie', compact('serie', 'garantias', 'reclamaciones', 'sustituciones'));
    }
}
