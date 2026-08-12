<?php

use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use Illuminate\Support\Facades\Route;

Route::get('/test/advance-services-to-approval', function () {
    // Solo permitir en desarrollo
    if (app()->environment('production')) {
        abort(403, 'Esta ruta no está disponible en producción');
    }

    try {
        // Obtener servicios externos que están en el paso 1 (Salida foránea)
        $salidaForaneaStep = ServiceStep::where('slug', 'salida_foranea')->first();
        $regresoForaneaStep = ServiceStep::where('slug', 'regreso_foranea')->first();
        $validacionOsStep = ServiceStep::where('slug', 'validacion_os')->first();

        if (!$salidaForaneaStep || !$regresoForaneaStep || !$validacionOsStep) {
            return response()->json([
                'error' => 'No se encontraron los pasos requeridos',
            ], 400);
        }

        // Obtener servicios externos en el paso de "Salida foránea"
        $services = Service::where('service_type', 'externo')
            ->where('current_step_id', $salidaForaneaStep->id)
            ->where('status', 'registrado')
            ->get();

        $advancedServices = [];

        foreach ($services as $service) {
            // Marcar el tracking actual como completado
            $currentTracking = ServiceTracking::where('service_id', $service->id)
                ->where('service_step_id', $salidaForaneaStep->id)
                ->where('status', 'pendiente')
                ->first();

            if ($currentTracking) {
                $currentTracking->update([
                    'status' => 'completado',
                    'finished_at' => now(),
                    'performed_by' => auth()->id() ?? 1,
                ]);
            }

            // Crear tracking para paso 2 (Regreso foráneo)
            ServiceTracking::create([
                'service_id' => $service->id,
                'service_step_id' => $regresoForaneaStep->id,
                'status' => 'completado',
                'performed_by' => auth()->id() ?? 1,
                'qr_token' => null,
                'qr_expires_at' => null,
                'notes' => 'Avanzado automáticamente para prueba',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            // Crear tracking para paso 3 (Validación OS - requiere aprobación)
            ServiceTracking::create([
                'service_id' => $service->id,
                'service_step_id' => $validacionOsStep->id,
                'status' => 'pendiente',
                'performed_by' => null,
                'qr_token' => 'test-qr-' . uniqid(),
                'qr_expires_at' => now()->addDay(),
                'notes' => null,
                'started_at' => now(),
            ]);

            // Actualizar el servicio al paso 3
            $service->update([
                'current_step_id' => $validacionOsStep->id,
                'qr_token' => 'test-qr-' . uniqid(),
                'qr_expires_at' => now()->addDay(),
                'status' => 'en_progreso',
            ]);

            $advancedServices[] = [
                'id' => $service->id,
                'service_number' => $service->service_number,
                'from_step' => 'Salida a mantenimiento foráneo',
                'to_step' => 'Validación OS',
                'status' => 'en_progreso',
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Servicios avanzados a paso de aprobación',
            'advanced_count' => count($advancedServices),
            'advanced_services' => $advancedServices,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
