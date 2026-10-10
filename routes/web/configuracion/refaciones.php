<?php

use App\Http\Controllers\Configuracion\RefaccionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Refacciones
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/configuracion/refaciones', [RefaccionController::class, 'index'])
        ->middleware('can:inventario.ver')->name('configuracion.refaciones.index');

    Route::get('/configuracion/refaciones/crear', [RefaccionController::class, 'create'])
        ->middleware('can:inventario.registrar')->name('configuracion.refaciones.create');

    Route::post('/configuracion/refaciones', [RefaccionController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('configuracion.refaciones.store');

    Route::delete('/configuracion/refaciones/{refaccion}', [RefaccionController::class, 'destroy'])
        ->middleware('can:inventario.eliminar')->name('configuracion.refaciones.destroy');
});
