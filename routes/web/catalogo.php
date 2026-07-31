<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CongressController;
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

    Route::get('/configuracion/categorias/editar/{category}', [CategoryController::class, 'edit'])
        ->name('configuracion.categorias.edit');

    Route::put('/configuracion/categorias/{category}', [CategoryController::class, 'update'])
        ->name('configuracion.categorias.update');

    Route::get('/configuracion/categorias/eliminar/{category}', [CategoryController::class, 'delete'])
        ->name('configuracion.categorias.delete');

    Route::delete('/configuracion/categorias/{category}', [CategoryController::class, 'destroy'])
        ->name('configuracion.categorias.destroy');

    Route::get('/configuracion/congresos/crear', [CongressController::class, 'create'])
        ->name('configuracion.congresos.create');

    Route::post('/configuracion/congresos', [CongressController::class, 'store'])
        ->name('configuracion.congresos.store');

    Route::get('/configuracion/congresos/{congress}', [CongressController::class, 'show'])
        ->name('configuracion.congresos.show');

    Route::get('/configuracion/congresos/editar/{congress}', [CongressController::class, 'edit'])
        ->name('configuracion.congresos.edit');

    Route::put('/configuracion/congresos/{congress}', [CongressController::class, 'update'])
        ->name('configuracion.congresos.update');

    Route::get('/configuracion/congresos/eliminar/{congress}', [CongressController::class, 'delete'])
        ->name('configuracion.congresos.delete');

    Route::delete('/configuracion/congresos/{congress}', [CongressController::class, 'destroy'])
        ->name('configuracion.congresos.destroy');
});
