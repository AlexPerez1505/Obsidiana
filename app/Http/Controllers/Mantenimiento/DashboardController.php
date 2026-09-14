<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\View\View;

/**
 * Tablero de inicio para el rol Mantenimiento: solo Órdenes de servicio.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $services = Service::with('customer')->latest()->get();

        $stats = [
            'total' => $services->count(),
            'registrados' => $services->where('status', 'registrado')->count(),
            'en_progreso' => $services->where('status', 'en_progreso')->count(),
            'entregados' => $services->where('status', 'entregado')->count(),
            'cancelados' => $services->where('status', 'cancelado')->count(),
        ];

        $ultimas = $services->take(5)->map(function (Service $service) {
            $cliente = trim(($service->customer?->nombre ?? '').' '.($service->customer?->apellido ?? '')) ?: 'Sin cliente';

            return [
                'os' => $service->service_number ?? ('OS-'.$service->id),
                'cliente' => $cliente,
                'estado' => $service->status ?? 'registrado',
                'fecha' => $service->created_at?->format('d/m/Y') ?? '—',
            ];
        });

        return view('mantenimiento.dashboard', [
            'stats' => $stats,
            'ultimas' => $ultimas,
        ]);
    }
}
