<?php

use App\Models\Service;
use App\Models\ServiceTracking;
use Illuminate\Support\Facades\Route;

Route::get('/test/diagnose-services', function () {
    // Solo permitir en desarrollo
    if (app()->environment('production')) {
        abort(403, 'Esta ruta no está disponible en producción');
    }

    try {
        // Obtener todos los servicios
        $services = Service::with(['currentStep', 'customer'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $servicesData = $services->map(function ($service) {
            $trackings = ServiceTracking::where('service_id', $service->id)
                ->with('serviceStep')
                ->orderBy('created_at')
                ->get();

            return [
                'id' => $service->id,
                'service_number' => $service->service_number,
                'service_type' => $service->service_type,
                'customer' => $service->customer?->nombre,
                'current_step' => [
                    'id' => $service->currentStep?->id,
                    'name' => $service->currentStep?->name,
                    'requires_approval' => $service->currentStep?->requires_approval,
                ],
                'status' => $service->status,
                'created_at' => $service->created_at,
                'trackings' => $trackings->map(fn ($t) => [
                    'id' => $t->id,
                    'step_name' => $t->serviceStep?->name,
                    'step_requires_approval' => $t->serviceStep?->requires_approval,
                    'status' => $t->status,
                    'created_at' => $t->created_at,
                ]),
            ];
        });

        // Obtener aprobaciones pendientes
        $pendingApprovals = ServiceTracking::with(['service.customer', 'serviceStep'])
            ->whereIn('status', ['pendiente', 'rechazado'])
            ->whereHas('serviceStep', fn ($q) => $q->where('requires_approval', true))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'services' => $servicesData,
            'pending_approvals_count' => $pendingApprovals->count(),
            'pending_approvals' => $pendingApprovals->map(fn ($a) => [
                'service_number' => $a->service->service_number,
                'service_type' => $a->service->service_type,
                'step_name' => $a->serviceStep->name,
                'status' => $a->status,
            ]),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
