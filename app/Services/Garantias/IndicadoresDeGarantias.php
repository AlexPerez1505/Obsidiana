<?php

namespace App\Services\Garantias;

use App\Models\Garantia;
use App\Models\GarantiaCosto;
use App\Models\GarantiaReclamacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Números del tablero y de reportes. Todo se calcula sobre garantías no
 * anuladas: una póliza anulada no cuenta como unidad cubierta.
 */
class IndicadoresDeGarantias
{
    public function resumen(): array
    {
        $mes = now()->startOfMonth();

        return [
            'activas' => Garantia::vigentes()->count(),
            'abiertas' => GarantiaReclamacion::abiertas()->count(),
            'atrasadas' => GarantiaReclamacion::atrasadas()->count(),
            'seguridad' => GarantiaReclamacion::abiertas()->where('es_seguridad', true)->count(),
            'por_vencer' => Garantia::porVencer()->count(),
            'en_reparacion' => GarantiaReclamacion::abiertas()->where('etapa', 'atencion')->count(),
            'costo_mes' => (float) GarantiaCosto::where('estado', 'aprobado')->where('created_at', '>=', $mes)->sum('monto'),
            'por_aprobar' => GarantiaCosto::where('estado', 'pendiente')->count(),
            'prestamos_fuera' => DB::table('garantia_sustituciones')->where('tipo', 'prestamo')->whereNull('devuelto_en')->count(),
        ];
    }

    /** Reclamaciones recibidas por mes (los últimos N meses, incluido el actual). */
    public function porMes(int $meses = 6): Collection
    {
        $desde = now()->startOfMonth()->subMonths($meses - 1);

        $conteo = GarantiaReclamacion::where('recibida_en', '>=', $desde)
            ->get(['recibida_en'])
            ->groupBy(fn ($r) => $r->recibida_en->format('Y-m'))
            ->map->count();

        return collect(range(0, $meses - 1))->map(function ($i) use ($desde, $conteo) {
            $m = $desde->copy()->addMonths($i);

            return ['mes' => $m, 'total' => (int) ($conteo[$m->format('Y-m')] ?? 0)];
        });
    }

    public function indicadores(?Carbon $desde = null): array
    {
        $recs = GarantiaReclamacion::with('costos')
            ->when($desde, fn ($q) => $q->where('recibida_en', '>=', $desde))
            ->get();
        $cerradas = $recs->whereNotNull('cerrada_en');

        $unidades = Garantia::where('estado', '!=', 'anulada')->count();
        $conFalla = $recs->pluck('garantia_id')->filter()->unique()->count();
        $reincidentes = $recs->whereNotNull('garantia_id')->groupBy('garantia_id')->filter(fn ($g) => $g->count() >= 2)->count();

        $respuesta = $recs->filter(fn ($r) => $r->primera_respuesta_en)
            ->map(fn ($r) => $r->recibida_en->diffInMinutes($r->primera_respuesta_en) / 60);

        $aTiempo = $cerradas->filter(fn ($r) => $r->limite_en && $r->cerrada_en->lte($r->limite_en))->count();

        $costos = $recs->flatMap->costos->where('estado', 'aprobado');
        $deFabricante = $costos->where('absorbe', 'fabricante');
        $calificadas = $cerradas->whereNotNull('satisfaccion');

        return [
            'unidades' => $unidades,
            'reclamaciones' => $recs->count(),
            'tasa_fallas' => $unidades ? round($conFalla / $unidades * 100, 1) : 0,
            'reincidentes' => $reincidentes,
            'horas_respuesta' => $respuesta->isNotEmpty() ? round($respuesta->avg(), 1) : null,
            'dias_resolucion' => $cerradas->isNotEmpty()
                ? round($cerradas->avg(fn ($r) => $r->recibida_en->diffInHours($r->cerrada_en) / 24), 1)
                : null,
            'costo_promedio' => $recs->count() ? round($costos->sum('monto') / $recs->count(), 2) : 0,
            'costo_total' => (float) $costos->sum('monto'),
            'cumplimiento' => $cerradas->count() ? round($aTiempo / $cerradas->count() * 100) : null,
            'satisfaccion' => $calificadas->isNotEmpty() ? round($calificadas->avg('satisfaccion'), 1) : null,
            'recuperable' => (float) $deFabricante->sum('monto'),
            'recuperado' => (float) $deFabricante->sum('recuperado'),
            'pct_recuperado' => $deFabricante->sum('monto') > 0
                ? round($deFabricante->sum('recuperado') / $deFabricante->sum('monto') * 100) : null,
            'no_procede' => $recs->where('dictamen', 'no_procede')->count(),
            'seguridad' => $recs->where('es_seguridad', true)->count(),
        ];
    }

    /**
     * Marcas y modelos con más problemas: unidades vendidas, reclamaciones,
     * tasa de falla y gasto.
     */
    public function rankingModelos(int $limite = 10): Collection
    {
        return $this->ranking(fn (Garantia $g) => trim(($g->marca ?: 'Sin marca').' '.($g->modelo ?: $g->descripcion)), $limite);
    }

    public function rankingProveedores(int $limite = 10): Collection
    {
        return $this->ranking(fn (Garantia $g) => $g->proveedor ?: null, $limite);
    }

    private function ranking(callable $clave, int $limite): Collection
    {
        $garantias = Garantia::where('estado', '!=', 'anulada')
            ->withCount('reclamaciones')
            ->withSum(['costos as gasto' => fn ($q) => $q->where('estado', 'aprobado')], 'monto')
            ->withSum(['costos as recuperado_sum' => fn ($q) => $q->where('estado', 'aprobado')], 'recuperado')
            ->get();

        return $garantias->groupBy($clave)
            ->reject(fn ($g, $k) => $k === '' || $k === null)
            ->map(fn ($grupo, $nombre) => [
                'nombre' => $nombre,
                'unidades' => $grupo->count(),
                'reclamaciones' => (int) $grupo->sum('reclamaciones_count'),
                'con_falla' => $grupo->where('reclamaciones_count', '>', 0)->count(),
                'tasa' => round($grupo->where('reclamaciones_count', '>', 0)->count() / max(1, $grupo->count()) * 100, 1),
                'gasto' => (float) $grupo->sum('gasto'),
                'recuperado' => (float) $grupo->sum('recuperado_sum'),
            ])
            ->filter(fn ($r) => $r['reclamaciones'] > 0)
            ->sortByDesc(fn ($r) => [$r['reclamaciones'], $r['gasto']])
            ->take($limite)
            ->values();
    }

    /**
     * Rentabilidad de una unidad: lo que se vendió contra lo que costó y lo
     * que se ha gastado en garantía (menos lo recuperado).
     */
    public function rentabilidad(Garantia $g): array
    {
        $costos = $g->costos()->where('estado', 'aprobado')->get();
        $porConcepto = $costos->groupBy('concepto')->map->sum('monto');
        $gastoNeto = (float) $costos->where('absorbe', '!=', 'cliente')->sum('monto') - (float) $costos->sum('recuperado');

        return [
            'precio' => $g->precio_venta !== null ? (float) $g->precio_venta : null,
            'costo' => $g->costo_adquisicion !== null ? (float) $g->costo_adquisicion : null,
            'por_concepto' => $porConcepto,
            'gasto' => (float) $costos->sum('monto'),
            'recuperado' => (float) $costos->sum('recuperado'),
            'del_cliente' => (float) $costos->where('absorbe', 'cliente')->sum('monto'),
            'gasto_neto' => $gastoNeto,
            'margen' => $g->precio_venta !== null && $g->costo_adquisicion !== null
                ? (float) $g->precio_venta - (float) $g->costo_adquisicion - $gastoNeto
                : null,
        ];
    }
}
