<?php

use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use Illuminate\Support\Facades\Route;

Route::get('/test/detailed-diagnose', function () {
    // Solo permitir en desarrollo
    if (app()->environment('production')) {
        abort(403, 'Esta ruta no está disponible en producción');
    }

    try {
        // Obtener todos los pasos
        $allSteps = ServiceStep::where('service_type', 'externo')
            ->orderBy('order')
            ->get()
            ->map(fn ($step) => [
                'id' => $step->id,
                'order' => $step->order,
                'name' => $step->name,
                'slug' => $step->slug,
                'requires_approval' => (bool) $step->requires_approval,
            ]);

        // Obtener todos los servicios externos
        $services = Service::where('service_type', 'externo')
            ->with(['currentStep', 'customer'])
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
                'customer' => $service->customer?->nombre,
                'status' => $service->status,
                'current_step' => [
                    'id' => $service->currentStep?->id,
                    'order' => $service->currentStep?->order,
                    'name' => $service->currentStep?->name,
                    'requires_approval' => (bool) $service->currentStep?->requires_approval,
                ],
                'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                'trackings_history' => $trackings->map(fn ($t) => [
                    'id' => $t->id,
                    'step_order' => $t->serviceStep?->order,
                    'step_name' => $t->serviceStep?->name,
                    'step_requires_approval' => (bool) $t->serviceStep?->requires_approval,
                    'status' => $t->status,
                    'created_at' => $t->created_at->format('Y-m-d H:i:s'),
                    'finished_at' => $t->finished_at?->format('Y-m-d H:i:s'),
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
            'message' => 'Diagnóstico detallado del flujo de servicios externos',
            'external_service_steps' => $allSteps,
            'services' => $servicesData,
            'pending_approvals_count' => $pendingApprovals->count(),
            'pending_approvals' => $pendingApprovals->map(fn ($a) => [
                'service_number' => $a->service->service_number,
                'customer' => $a->service->customer?->nombre,
                'step_name' => $a->serviceStep->name,
                'step_requires_approval' => (bool) $a->serviceStep->requires_approval,
                'status' => $a->status,
                'created_at' => $a->created_at->format('Y-m-d H:i:s'),
            ]),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
