<?php

use App\Http\Controllers\EquipmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tipos de Equipo
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/configuracion/tipos-equipo', [EquipmentController::class, 'index'])
        ->name('configuracion.tipos_equipo.index');

    Route::get('/configuracion/tipos-equipo/crear', [EquipmentController::class, 'create'])
        ->name('configuracion.tipos_equipo.create');

    Route::post('/configuracion/tipos-equipo', [EquipmentController::class, 'store'])
        ->name('configuracion.tipos_equipo.store');

    Route::get('/configuracion/tipos-equipo/subtipos', [EquipmentController::class, 'subtypes'])
        ->name('configuracion.tipos_equipo.subtypes');

    Route::get('/configuracion/tipos-equipo/modelos', [EquipmentController::class, 'models'])
        ->name('configuracion.tipos_equipo.models');
});
