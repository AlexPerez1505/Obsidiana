<?php

use App\Http\Controllers\Services\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-servicios/historial-servicios')
    ->group(function () {
        // Índice del historial de servicios
        Route::get('/', [ServiceController::class, 'index'])
            ->name('gestion.servicios.historial');

        // Selección de tipo de servicio (nuevo servicio)
        Route::get('/nuevo-servicio', [ServiceController::class, 'create'])
            ->name('gestion.servicios.nuevo');

        // Formularios de registro según tipo de mantenimiento
        Route::view('/nuevo-servicio/interno', 'structure.gestion_servicios.Historial_se.registro_NS.Interno.formulario_int')
            ->name('gestion.servicios.nuevo.interno');

        Route::get('/nuevo-servicio/externo', [ServiceController::class, 'createExternal'])
            ->name('gestion.servicios.nuevo.externo');

        // Paso 2: Registro del equipo
        Route::get('/nuevo-servicio/externo/equipo', [ServiceController::class, 'createEquipment'])
            ->name('gestion.servicios.nuevo.externo.equipo');
        Route::post('/nuevo-servicio/externo/equipo', [ServiceController::class, 'storeEquipment'])
            ->name('gestion.servicios.nuevo.externo.equipo.store');

        // Paso 3: Selección del técnico
        Route::get('/nuevo-servicio/externo/tecnico', [ServiceController::class, 'createTechnician'])
            ->name('gestion.servicios.nuevo.externo.tecnico');
        Route::post('/nuevo-servicio/externo/tecnico', [ServiceController::class, 'storeTechnician'])
            ->name('gestion.servicios.nuevo.externo.tecnico.store');
        Route::post('/nuevo-servicio/externo/guardar', [ServiceController::class, 'storeService'])
            ->name('gestion.servicios.nuevo.externo.guardar');

        // Resumen de orden tras guardar
        Route::get('/nuevo-servicio/externo/resumen/{service}', [ServiceController::class, 'showSummary'])
            ->name('gestion.servicios.nuevo.externo.resumen');

        // Eliminar orden de servicio
        Route::delete('/{service}', [ServiceController::class, 'destroy'])
            ->name('gestion.servicios.destroy');

        // Completar paso actual desde modal de mantenimiento
        Route::post('/{service}/complete-step', [ServiceController::class, 'completeCurrentStep'])
            ->name('gestion.servicios.completeStep');
    });
