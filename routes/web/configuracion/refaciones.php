<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Refacciones
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/configuracion/refaciones', function () {
        return view('structure.Configuracion.refaciones.menu_refaciones');
    })->name('configuracion.refaciones.index');

    Route::get('/configuracion/refaciones/crear', function () {
        return view('structure.Configuracion.refaciones.c_refaciones');
    })->name('configuracion.refaciones.create');

    Route::post('/configuracion/refaciones', function () {
        return redirect()->route('configuracion.refaciones.index')
            ->with('status', 'Refacción guardada (modo de prueba).');
    })->name('configuracion.refaciones.store');
});
