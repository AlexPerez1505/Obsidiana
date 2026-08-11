<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/test/cleanup-services', function () {
    // Solo permitir en desarrollo
    if (app()->environment('production')) {
        abort(403, 'Esta ruta no está disponible en producción');
    }

    try {
        // Eliminar trackings de servicios de prueba
        $deletedTrackings = DB::table('service_trackings')
            ->whereIn('service_id', function ($query) {
                $query->select('id')
                    ->from('services')
                    ->where('service_number', 'like', 'OS-TEST-%');
            })
            ->delete();

        // Eliminar servicios de prueba
        $deletedServices = DB::table('services')
            ->where('service_number', 'like', 'OS-TEST-%')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicios de prueba eliminados correctamente',
            'deleted_trackings' => $deletedTrackings,
            'deleted_services' => $deletedServices,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
