<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\LoginLog;
use App\Models\MaterialRequest;
use App\Models\Producto;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->stats();
        $data['user'] = $request->user();
        $data['days'] = $this->last7Days();

        return view('dashboard.index', $data);
    }

    public function metrics(Request $request): JsonResponse
    {
        return response()->json($this->stats());
    }

    private function stats(): array
    {
        $days = $this->last7Days();
        $now = now();

        $counts = [
            'customersCount' => Customer::count(),
            'productsCount'  => Producto::count(),
            'salesTotal'     => (float) Cotizacion::where('estado', 'remision')->sum('total'),
            'quotesCount'    => Cotizacion::where('estado', 'cotizacion')->count(),
        ];

        $customerTrend = $days->map(fn ($d) => Customer::whereDate('created_at', $d)->count())->values();
        $productTrend  = $days->map(fn ($d) => Producto::whereDate('created_at', $d)->count())->values();
        $salesTrend    = $days->map(fn ($d) => (float) Cotizacion::where('estado', 'remision')->whereDate('created_at', $d)->sum('total'))->values();
        $quotesTrend   = $days->map(fn ($d) => Cotizacion::where('estado', 'cotizacion')->whereDate('created_at', $d)->count())->values();

        return [
            ...$counts,
            'customerTrend' => $customerTrend,
            'productTrend'  => $productTrend,
            'salesTrend'    => $salesTrend,
            'quotesTrend'   => $quotesTrend,
            'customerChange' => $this->pctChange(
                Customer::whereBetween('created_at', [$now->copy()->subDays(6)->startOfDay(), $now->endOfDay()])->count(),
                Customer::whereBetween('created_at', [$now->copy()->subDays(13)->startOfDay(), $now->copy()->subDays(7)->endOfDay()])->count()
            ),
            'productChange' => $this->pctChange(
                Producto::whereBetween('created_at', [$now->copy()->subDays(6)->startOfDay(), $now->endOfDay()])->count(),
                Producto::whereBetween('created_at', [$now->copy()->subDays(13)->startOfDay(), $now->copy()->subDays(7)->endOfDay()])->count()
            ),
            'salesChange' => $this->pctChange(
                (float) Cotizacion::where('estado', 'remision')->whereBetween('created_at', [$now->copy()->subDays(6)->startOfDay(), $now->endOfDay()])->sum('total'),
                (float) Cotizacion::where('estado', 'remision')->whereBetween('created_at', [$now->copy()->subDays(13)->startOfDay(), $now->copy()->subDays(7)->endOfDay()])->sum('total')
            ),
            'quoteChange' => $this->pctChange(
                Cotizacion::where('estado', 'cotizacion')->whereBetween('created_at', [$now->copy()->subDays(6)->startOfDay(), $now->endOfDay()])->count(),
                Cotizacion::where('estado', 'cotizacion')->whereBetween('created_at', [$now->copy()->subDays(13)->startOfDay(), $now->copy()->subDays(7)->endOfDay()])->count()
            ),
            'alerts'     => $this->alerts(),
            'activities' => $this->recentActivities(),
            'agenda'     => $this->agenda(),
        ];
    }

    private function last7Days()
    {
        return collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString())->values();
    }

    private function pctChange(float $current, float $prev): array
    {
        if ($prev == 0) {
            return ['value' => $current > 0 ? 100.0 : 0.0, 'positive' => $current >= 0];
        }

        $change = round((($current - $prev) / $prev) * 100, 1);

        return ['value' => abs($change), 'positive' => $change >= 0];
    }

    private function alerts(): array
    {
        return [
            [
                'type'    => 'stock',
                'count'   => Producto::where('stock', '<=', 2)->count(),
                'message' => 'productos con stock bajo',
                'route'   => route('inventory.productos.index'),
                'color'   => 'warn',
                'icon'    => 'box',
            ],
            [
                'type'    => 'service',
                'count'   => Service::whereNull('finished_at')->count(),
                'message' => 'servicios pendientes',
                'route'   => route('gestion.servicios.historial'),
                'color'   => 'danger',
                'icon'    => 'tool',
            ],
            [
                'type'    => 'material',
                'count'   => MaterialRequest::where('status', MaterialRequest::STATUS_PENDING)->count(),
                'message' => 'solicitudes de material pendientes',
                'route'   => route('admin.materials.index'),
                'color'   => 'warn',
                'icon'    => 'package',
            ],
            [
                'type'    => 'quote',
                'count'   => Cotizacion::where('estado', 'cotizacion')->count(),
                'message' => 'cotizaciones por cerrar',
                'route'   => route('commercial.cotizaciones.index'),
                'color'   => 'info',
                'icon'    => 'file-text',
            ],
        ];
    }

    private function recentActivities()
    {
        $cotizaciones = Cotizacion::with('cliente')->latest()->limit(4)->get()->map(fn ($c) => [
            'message' => $c->estado === 'remision' ? 'Venta registrada' : 'Cotización creada',
            'detail'  => '#' . $c->id . ' — ' . (optional($c->cliente)->nombre . ' ' . optional($c->cliente)->apellido),
            'time'    => $c->created_at,
            'url'     => route('commercial.cotizaciones.show', $c),
            'icon'    => 'cart',
            'color'   => 'orange',
        ]);

        $clientes = Customer::latest()->limit(4)->get()->map(fn ($c) => [
            'message' => 'Cliente registrado',
            'detail'  => trim($c->nombre . ' ' . $c->apellido),
            'time'    => $c->created_at,
            'url'     => route('commercial.clientes.show', $c),
            'icon'    => 'users',
            'color'   => 'blue',
        ]);

        $productos = Producto::latest()->limit(4)->get()->map(fn ($p) => [
            'message' => 'Producto actualizado',
            'detail'  => trim(($p->tipo_equipo ?? '') . ' ' . ($p->marca ?? '') . ' ' . ($p->modelo ?? '')),
            'time'    => $p->created_at,
            'url'     => route('inventory.productos.index'),
            'icon'    => 'box',
            'color'   => 'green',
        ]);

        $logs = LoginLog::with('user')->latest('logged_at')->limit(4)->get()->map(fn ($l) => [
            'message' => 'Inicio de sesión',
            'detail'  => optional($l->user)->name ?? 'Usuario del sistema',
            'time'    => $l->logged_at,
            'url'     => '#',
            'icon'    => 'login',
            'color'   => 'muted',
        ]);

        return $cotizaciones->concat($clientes)->concat($productos)->concat($logs)
            ->sortByDesc('time')
            ->take(8)
            ->values();
    }

    private function agenda()
    {
        return AgendaEvent::whereDate('start_date', '>=', now()->toDateString())
            ->whereDate('start_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->limit(6)
            ->get();
    }
}
