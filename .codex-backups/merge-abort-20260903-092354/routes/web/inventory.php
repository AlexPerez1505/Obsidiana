<?php

use App\Http\Controllers\EquipoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión de Inventario
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-inventario/equipos', [EquipoController::class, 'index'])
        ->name('inventory.equipos.index');

    Route::get('/gestion-inventario/equipos/crear', [EquipoController::class, 'create'])
        ->name('inventory.equipos.create');

    Route::post('/gestion-inventario/equipos', [EquipoController::class, 'store'])
        ->name('inventory.equipos.store');
});
