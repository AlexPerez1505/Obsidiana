<?php

use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use Illuminate\Support\Facades\Route;

Route::get('/test/fix-service-flow', function () {
    // Solo permitir en desarrollo
    if (app()->environment('production')) {
        abort(403, 'Esta ruta no está disponible en producción');
    }

    try {
        // Obtener los pasos
        $salidaForaneaStep = ServiceStep::where('slug', 'salida_foranea')->first();
        $regresoForaneaStep = ServiceStep::where('slug', 'regreso_foranea')->first();
        $validacionOsStep = ServiceStep::where('slug', 'validacion_os')->first();

        if (!$regresoForaneaStep || !$validacionOsStep) {
            return response()->json([
                'error' => 'No se encontraron los pasos requeridos',
            ], 400);
        }

        // Obtener servicios externos que están en paso 1 (Salida foránea)
        $services = Service::where('service_type', 'externo')
            ->where('current_step_id', $salidaForaneaStep->id)
            ->get();

        $fixedServices = [];

        foreach ($services as $service) {
            // Eliminar el tracking del paso 1
            ServiceTracking::where('service_id', $service->id)
                ->where('service_step_id', $salidaForaneaStep->id)
                ->delete();

            // Crear tracking para paso 2 (Regreso foráneo) - completado
            ServiceTracking::create([
                'service_id' => $service->id,
                'service_step_id' => $regresoForaneaStep->id,
                'status' => 'completado',
                'performed_by' => auth()->id() ?? 1,
                'qr_token' => null,
                'qr_expires_at' => null,
                'notes' => 'Corregido automáticamente - paso saltado',
                'started_at' => now()->subHours(2),
                'finished_at' => now()->subHour(),
            ]);

            // Crear tracking para paso 3 (Validación OS) - pendiente de aprobación
            ServiceTracking::create([
                'service_id' => $service->id,
                'service_step_id' => $validacionOsStep->id,
                'status' => 'pendiente',
                'performed_by' => null,
                'qr_token' => 'qr-' . uniqid(),
                'qr_expires_at' => now()->addDay(),
                'notes' => null,
                'started_at' => now(),
                'finished_at' => null,
            ]);

            // Actualizar el servicio al paso 3
            $service->update([
                'current_step_id' => $validacionOsStep->id,
                'qr_token' => 'qr-' . uniqid(),
                'qr_expires_at' => now()->addDay(),
                'status' => 'en_progreso',
            ]);

            $fixedServices[] = [
                'id' => $service->id,
                'service_number' => $service->service_number,
                'customer' => $service->customer?->nombre,
                'new_step' => 'Validación OS (requiere aprobación)',
                'status' => 'en_progreso',
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Servicios corregidos al flujo correcto',
            'fixed_count' => count($fixedServices),
            'fixed_services' => $fixedServices,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
