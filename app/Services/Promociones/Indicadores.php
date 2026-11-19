<?php

namespace App\Services\Promociones;

use App\Models\Campana;
use App\Models\CampanaDestinatario;
use App\Models\Cupon;
use App\Models\CuponUso;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores del módulo por periodo, con su serie diaria y el periodo
 * anterior de la misma duración. Solo datos reales: si el periodo anterior
 * no tiene movimientos no hay comparación, y si no hay movimientos no hay
 * gráfica.
 */
class Indicadores
{
    public const PERIODOS = ['7d' => 'Últimos 7 días', '30d' => 'Últimos 30 días', '90d' => 'Últimos 90 días', 'mes' => 'Este mes'];

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: CarbonImmutable, 3: CarbonImmutable} desde, hasta, desde anterior, hasta anterior */
    public static function rango(string $periodo): array
    {
        $hoy = CarbonImmutable::now();
        if ($periodo === 'mes') {
            $desde = $hoy->startOfMonth();
            $dias = $desde->diffInDays($hoy->endOfDay()) + 1;
        } else {
            $dias = (int) rtrim(array_key_exists($periodo, self::PERIODOS) ? $periodo : '30d', 'd');
            $desde = $hoy->subDays($dias - 1)->startOfDay();
        }
        $hasta = $hoy->endOfDay();
        $antHasta = $desde->subSecond();
        $antDesde = $desde->subDays((int) ceil($dias));

        return [$desde, $hasta, $antDesde, $antHasta];
    }

    public static function periodoValido(?string $p): string
    {
        return array_key_exists((string) $p, self::PERIODOS) ? $p : '30d';
    }

    public function calcular(string $periodo): array
    {
        [$desde, $hasta, $aDesde, $aHasta] = self::rango($periodo);

        $usos = fn ($d, $h) => CuponUso::where('estado', CuponUso::CONSUMIDO)->whereBetween('consumido_en', [$d, $h]);
        $ventas = fn ($d, $h) => Venta::whereNotNull('campana_id')->where('estado', '!=', 'cancelada')->whereBetween('created_at', [$d, $h]);
        $envios = fn ($d, $h) => CampanaDestinatario::where('es_prueba', false)
            ->whereIn('estado', [CampanaDestinatario::ENVIADO, CampanaDestinatario::ENTREGADO, CampanaDestinatario::LEIDO])
            ->whereBetween('enviado_en', [$d, $h]);

        $kpi = function (string $clave, callable $q, string $agregado, string $col, string $colFecha) use ($desde, $hasta, $aDesde, $aHasta) {
            $actual = (float) ($agregado === 'count' ? $q($desde, $hasta)->count() : $q($desde, $hasta)->sum($col));
            $anterior = (float) ($agregado === 'count' ? $q($aDesde, $aHasta)->count() : $q($aDesde, $aHasta)->sum($col));

            $expr = $agregado === 'count' ? 'COUNT(*)' : "COALESCE(SUM({$col}),0)";
            $porDia = $q($desde, $hasta)->selectRaw("DATE({$colFecha}) d, {$expr} v")->groupBy('d')->pluck('v', 'd');
            $serie = [];
            for ($dia = $desde; $dia <= $hasta; $dia = $dia->addDay()) {
                $serie[] = (float) ($porDia[$dia->toDateString()] ?? 0);
            }

            return [
                'clave' => $clave,
                'valor' => $actual,
                'anterior' => $anterior,
                // Sin datos del periodo anterior no se calcula variación (no se inventa).
                'variacion' => $anterior > 0 ? round(($actual - $anterior) / $anterior * 100, 1) : null,
                'serie' => array_sum($serie) > 0 ? $serie : [],
            ];
        };

        return [
            'periodo' => $periodo,
            'desde' => $desde,
            'hasta' => $hasta,
            'activas' => Campana::whereIn('estado', [Campana::PROGRAMADA, Campana::ENVIANDO, Campana::PAUSADA])->count(),
            'enviados' => $kpi('enviados', $envios, 'count', 'id', 'enviado_en'),
            'usados' => $kpi('usados', $usos, 'count', 'id', 'consumido_en'),
            'ventas' => $kpi('ventas', $ventas, 'sum', 'total', 'created_at'),
            'ventas_n' => $ventas($desde, $hasta)->count(),
            'descontado' => $kpi('descontado', $usos, 'sum', 'descuento', 'consumido_en'),
        ];
    }

    /** Contadores de las pestañas. */
    public function contadores(): array
    {
        return [
            'campanas' => Campana::whereIn('estado', [Campana::PROGRAMADA, Campana::ENVIANDO, Campana::PAUSADA])->count(),
            'cupones' => Cupon::whereNull('origen_cupon_id')->where('estado', 'activo')
                ->where(fn ($v) => $v->whereNull('vence_en')->orWhere('vence_en', '>=', now()))->count(),
        ];
    }

    /** Los cupones que más se usaron en el periodo. */
    public function topCupones(string $periodo, int $n = 5): array
    {
        [$desde, $hasta] = self::rango($periodo);

        return CuponUso::where('mkt_cupon_usos.estado', CuponUso::CONSUMIDO)->whereBetween('mkt_cupon_usos.consumido_en', [$desde, $hasta])
            ->join('mkt_cupones as c', 'c.id', '=', 'mkt_cupon_usos.cupon_id')
            ->selectRaw('COALESCE(c.origen_cupon_id, c.id) as cid, COUNT(*) as usos, SUM(mkt_cupon_usos.descuento) as descontado')
            ->groupBy(DB::raw('COALESCE(c.origen_cupon_id, c.id)'))
            ->orderByDesc('usos')->limit($n)->get()
            ->map(function ($r) {
                $c = Cupon::find($r->cid);

                return ['id' => $r->cid, 'codigo' => $c?->codigo, 'nombre' => $c?->nombre, 'usos' => (int) $r->usos, 'descontado' => (float) $r->descontado];
            })->all();
    }
}
