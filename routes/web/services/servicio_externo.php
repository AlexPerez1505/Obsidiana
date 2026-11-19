<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Services\ServicioExternoController;
use App\Models\Service;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/servicio-externo', function () {
        $services = Service::with(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep'])
            ->whereIn('service_type', ['interno', 'externo'])
            ->whereIn('status', ['aprobado', 'en_progreso', 'completado'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.Servicio_Externo.Index', compact('services'));
    })->name('gestion.servicios.servicio_externo');

    Route::get('/gestion-servicios/servicio-externo/interno', function () {
        $services = Service::with(['customer', 'serviceEquipment', 'internalTechnician', 'currentStep'])
            ->where('service_type', 'interno')
            ->whereIn('status', ['aprobado', 'en_progreso', 'completado', 'entregado'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.Servicio_Externo.Interno', compact('services'));
    })->name('gestion.servicios.servicio_externo.interno');

    Route::get('/gestion-servicios/servicio-externo/externo', function () {
        $services = Service::with(['customer', 'serviceEquipment', 'externalTechnician', 'currentStep'])
            ->where('service_type', 'externo')
            ->whereIn('status', ['aprobado', 'en_progreso', 'completado', 'entregado'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.Servicio_Externo.Externo', compact('services'));
    })->name('gestion.servicios.servicio_externo.externo');

    Route::get('/gestion-servicios/servicio-externo/{service}', [ServicioExternoController::class, 'show'])
        ->name('gestion.servicios.servicio_externo.ver');
    Route::post('/gestion-servicios/servicio-externo/{service}/finalizar', [ServicioExternoController::class, 'finish'])
        ->middleware('can:servicios.crear')->name('gestion.servicios.servicio_externo.finalizar');
    Route::post('/gestion-servicios/servicio-externo/{service}/garantia', [ServicioExternoController::class, 'updateGarantia'])
        ->middleware('can:servicios.crear')->name('gestion.servicios.servicio_externo.garantia');
    Route::get('/gestion-servicios/servicio-externo/{service}/remision', [ServicioExternoController::class, 'remision'])
        ->name('gestion.servicios.servicio_externo.remision');

    Route::get('/gestion-servicios/servicio-externo/{service}/orden-servicio', [ServicioExternoController::class, 'ordenServicio'])
        ->name('gestion.servicios.servicio_externo.orden_servicio');
    Route::post('/gestion-servicios/servicio-externo/{service}/orden-servicio/paso2', [ServicioExternoController::class, 'ordenServicioPaso2'])
        ->middleware('can:servicios.crear')->name('gestion.servicios.servicio_externo.orden_servicio.paso2');
    Route::post('/gestion-servicios/servicio-externo/{service}/orden-servicio/paso3', [ServicioExternoController::class, 'ordenServicioPaso3'])
        ->middleware('can:servicios.crear')->name('gestion.servicios.servicio_externo.orden_servicio.paso3');
    Route::post('/gestion-servicios/servicio-externo/{service}/orden-servicio/paso4', [ServicioExternoController::class, 'ordenServicioPaso4'])
        ->middleware('can:servicios.crear')->name('gestion.servicios.servicio_externo.orden_servicio.paso4');
    Route::post('/gestion-servicios/servicio-externo/{service}/orden-servicio/generar', [ServicioExternoController::class, 'ordenServicioGenerar'])
        ->middleware('can:servicios.crear')->name('gestion.servicios.servicio_externo.orden_servicio.generar');
});
