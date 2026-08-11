<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/test/force-cleanup-all-services', function () {
    // Solo permitir en desarrollo
    if (app()->environment('production')) {
        abort(403, 'Esta ruta no está disponible en producción');
    }

    try {
        // Primero, obtener todos los IDs de service_steps que queremos eliminar
        $stepIds = DB::table('service_steps')
            ->whereIn('slug', [
                'autorizacion_admin',
                'entrega_cierre',
                'salida_foranea',
                'regreso_foranea',
                'validacion_os',
                'salida_cliente',
            ])
            ->pluck('id')
            ->toArray();

        // Obtener todos los servicios que usan estos pasos
        $serviceIds = DB::table('services')
            ->whereIn('current_step_id', $stepIds)
            ->pluck('id')
            ->toArray();

        $deletedTrackings = 0;
        $deletedServices = 0;

        if (!empty($serviceIds)) {
            // Eliminar trackings de estos servicios
            $deletedTrackings = DB::table('service_trackings')
                ->whereIn('service_id', $serviceIds)
                ->delete();

            // Eliminar los servicios
            $deletedServices = DB::table('services')
                ->whereIn('id', $serviceIds)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Todos los servicios que usan estos pasos fueron eliminados',
            'deleted_trackings' => $deletedTrackings,
            'deleted_services' => $deletedServices,
            'step_ids' => $stepIds,
            'service_ids' => $serviceIds,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
