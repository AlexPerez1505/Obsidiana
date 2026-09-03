<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Panorama de cobranza y ventas.
 *
 * Responde de un vistazo: cuánto me deben, quién está atrasado, cuánto
 * vendí este mes y qué asesor vende más.
 *
 * Las gráficas se calculan con agregados en SQL. La lista de deudores sí
 * se arma en PHP, porque el saldo depende de los cobros de cada venta y esa
 * lógica vive en el modelo; con miles de ventas habría que bajarla a SQL.
 */
class CobranzaController extends Controller
{
    private const MESES_GRAFICA = 12;

    public function index(Request $request): View
    {
        $estado = $request->get('estado', 'deben');
        $asesorId = $request->integer('asesor') ?: null;
        $buscar = trim((string) $request->get('buscar', ''));

        $ventas = Venta::with(['customer', 'seller', 'pagos.cobros', 'cobros'])
            ->when($asesorId, fn ($q) => $q->where('seller_id', $asesorId))
            ->latest()
            ->get();

        // Se calcula una sola vez por venta y se reutiliza en todo lo demás.
        $filas = $ventas->map(fn (Venta $v) => $this->fila($v));

        return view('structure.commercial_management.cobranza.index', [
            'filas' => $this->filtrar($filas, $estado, $buscar),
            'resumen' => $this->resumen($filas),
            'porMes' => $this->porMes(),
            'asesores' => $this->rankingAsesores(),
            'listaAsesores' => User::orderBy('name')->get(['id', 'name']),
            'estado' => $estado,
            'asesorId' => $asesorId,
            'buscar' => $buscar,
            'conteos' => [
                'todas' => $filas->count(),
                'deben' => $filas->where('saldo', '>', 0.009)->count(),
                'atrasadas' => $filas->where('atrasada', true)->count(),
                'pagadas' => $filas->where('saldo', '<=', 0.009)->count(),
            ],
        ]);
    }

    /** Datos ya masticados de una venta, para no recalcular en la vista. */
    private function fila(Venta $venta): array
    {
        $pendientes = $venta->pagos->filter(fn ($p) => $p->saldo() > 0.009);

        $proxima = $pendientes->filter(fn ($p) => $p->fecha)->sortBy('fecha')->first();
        $vencida = $pendientes->first(fn ($p) => $p->vencido());

        return [
            'venta' => $venta,
            'cliente' => trim(($venta->customer->nombre ?? '') . ' ' . ($venta->customer->apellido ?? '')) ?: 'Sin cliente',
            'asesor' => $venta->seller?->name ?? 'Sin asesor',
            'total' => $venta->montoExigible(),
            'cobrado' => $venta->totalCobrado(),
            'saldo' => $venta->saldo(),
            'avance' => $venta->avance(),
            'estado' => $venta->estadoPago(),
            'proxima' => $proxima?->fecha,
            'atrasada' => (bool) $vencida,
            // Días desde la parcialidad vencida más antigua.
            'dias_atraso' => $vencida?->fecha ? (int) $vencida->fecha->diffInDays(now()) : 0,
            'vencido_monto' => (float) $pendientes->filter(fn ($p) => $p->vencido())->sum(fn ($p) => $p->saldo()),
        ];
    }

    private function filtrar($filas, string $estado, string $buscar)
    {
        $filas = match ($estado) {
            'deben' => $filas->where('saldo', '>', 0.009),
            'atrasadas' => $filas->where('atrasada', true),
            'pagadas' => $filas->where('saldo', '<=', 0.009),
            default => $filas,
        };

        if ($buscar !== '') {
            $aguja = mb_strtolower($buscar);

            $filas = $filas->filter(fn ($f) => str_contains(mb_strtolower(
                $f['venta']->folio . ' ' . $f['cliente'] . ' ' . $f['asesor']
            ), $aguja));
        }

        // Primero lo más atrasado: es lo que hay que perseguir.
        return $filas->sortByDesc(fn ($f) => [$f['dias_atraso'], $f['saldo']])->values();
    }

    private function resumen($filas): array
    {
        $inicioMes = now()->startOfMonth();

        return [
            'ventas_total' => (float) $filas->sum('total'),
            'cobrado_total' => (float) $filas->sum('cobrado'),
            'por_cobrar' => (float) $filas->sum('saldo'),
            'vencido' => (float) $filas->sum('vencido_monto'),
            'clientes_deben' => $filas->where('saldo', '>', 0.009)->count(),
            'clientes_atrasados' => $filas->where('atrasada', true)->count(),

            'ventas_mes' => (float) Venta::where('created_at', '>=', $inicioMes)->sum('total'),
            'ventas_mes_cantidad' => Venta::where('created_at', '>=', $inicioMes)->count(),
            'cobrado_mes' => (float) Cobro::where('fecha', '>=', $inicioMes)->sum('monto'),
        ];
    }

    /**
     * Ventas generadas y dinero cobrado, mes a mes.
     *
     * Son dos cosas distintas y conviene verlas juntas: se puede vender
     * mucho un mes y cobrarlo tres meses después.
     */
    private function porMes(): array
    {
        $desde = now()->startOfMonth()->subMonths(self::MESES_GRAFICA - 1);

        $ventas = Venta::where('created_at', '>=', $desde)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, SUM(total) as monto")
            ->groupBy('mes')
            ->pluck('monto', 'mes');

        $cobros = Cobro::where('fecha', '>=', $desde)
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') as mes, SUM(monto) as monto")
            ->groupBy('mes')
            ->pluck('monto', 'mes');

        $meses = [];

        for ($i = 0; $i < self::MESES_GRAFICA; $i++) {
            $fecha = (clone $desde)->addMonths($i);
            $clave = $fecha->format('Y-m');

            $meses[] = [
                'etiqueta' => ucfirst($fecha->locale('es')->isoFormat('MMM')),
                'anio' => $fecha->format('y'),
                'vendido' => (float) ($ventas[$clave] ?? 0),
                'cobrado' => (float) ($cobros[$clave] ?? 0),
            ];
        }

        $maximo = collect($meses)->flatMap(fn ($m) => [$m['vendido'], $m['cobrado']])->max() ?: 0;

        return ['meses' => $meses, 'maximo' => $maximo];
    }

    /** Quién vende más, y cuánto de eso ya se cobró. */
    private function rankingAsesores(): array
    {
        $vendido = Venta::selectRaw('seller_id, COUNT(*) as ventas, SUM(total) as monto')
            ->groupBy('seller_id')
            ->get()
            ->keyBy('seller_id');

        $cobrado = Cobro::join('ventas', 'cobros.venta_id', '=', 'ventas.id')
            ->selectRaw('ventas.seller_id, SUM(cobros.monto) as monto')
            ->groupBy('ventas.seller_id')
            ->pluck('monto', 'seller_id');

        $nombres = User::whereIn('id', $vendido->keys()->filter())->pluck('name', 'id');

        $filas = $vendido->map(fn ($v, $id) => [
            'nombre' => $nombres[$id] ?? 'Sin asesor',
            'ventas' => (int) $v->ventas,
            'monto' => (float) $v->monto,
            'cobrado' => (float) ($cobrado[$id] ?? 0),
        ])->sortByDesc('monto')->values()->all();

        $maximo = collect($filas)->max('monto') ?: 0;

        return ['filas' => $filas, 'maximo' => $maximo];
    }
}
