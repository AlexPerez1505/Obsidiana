<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CongressEventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catálogo (categorías, congresos, etc.)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/configuracion/catalogos', [CategoryController::class, 'index'])
        ->name('configuracion.catalogos.index');

    Route::get('/configuracion/categorias/crear', [CategoryController::class, 'create'])
        ->name('configuracion.categorias.create');

    Route::post('/configuracion/categorias', [CategoryController::class, 'store'])
        ->name('configuracion.categorias.store');

    Route::get('/configuracion/congresos/crear', [CongressEventController::class, 'create'])
        ->name('configuracion.congresos.create');

    Route::post('/configuracion/congresos', [CongressEventController::class, 'store'])
        ->name('configuracion.congresos.store');
});
