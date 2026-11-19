<?php

use App\Http\Controllers\Agenda\AgendaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agenda empresarial y personal
|--------------------------------------------------------------------------
| Una pantalla (calendario + panel lateral) y sus endpoints JSON. Cada uno
| revisa permisos en el servidor; lo que no le toca ver a alguien es 404.
*/
Route::middleware(['auth', 'verified', 'approved', 'can:agenda.ver'])->prefix('agenda')->name('agenda.')->group(function () {
    Route::get('/', [AgendaController::class, 'index'])->name('index');
    Route::get('/datos', [AgendaController::class, 'datos'])->name('datos');
    Route::get('/paneles', [AgendaController::class, 'paneles'])->name('paneles');
    Route::get('/clientes', [AgendaController::class, 'clientes'])->name('clientes');
    Route::get('/carga', [AgendaController::class, 'carga'])->name('carga');
    Route::get('/exportar.ics', [AgendaController::class, 'ics'])->name('ics');
    Route::post('/preferencias', [AgendaController::class, 'preferencias'])->name('preferencias');

    Route::post('/actividades', [AgendaController::class, 'store'])->name('store');

    Route::prefix('item/{key}')->where(['key' => '[sa]-\d+'])->group(function () {
        Route::get('/', [AgendaController::class, 'show'])->name('item');
        Route::put('/', [AgendaController::class, 'update'])->name('update');
        Route::post('/mover', [AgendaController::class, 'mover'])->name('mover');
        Route::post('/estado', [AgendaController::class, 'estado'])->name('estado');
        Route::post('/cancelar', [AgendaController::class, 'cancelar'])->name('cancelar');
        Route::post('/reasignar', [AgendaController::class, 'reasignar'])->name('reasignar');
        Route::post('/responder', [AgendaController::class, 'responder'])->name('responder');
        Route::post('/duplicar', [AgendaController::class, 'duplicar'])->name('duplicar');
        Route::post('/comentarios', [AgendaController::class, 'comentar'])->name('comentar');
        Route::post('/checklist', [AgendaController::class, 'agregarPaso'])->name('checklist.store');
        Route::post('/posponer', [AgendaController::class, 'posponer'])->name('posponer');
    });

    Route::post('/checklist/{paso}', [AgendaController::class, 'marcarPaso'])->whereNumber('paso')->name('checklist.marcar');

    Route::get('/equipos', [AgendaController::class, 'equipos'])->name('equipos.index');
    Route::post('/equipos', [AgendaController::class, 'guardarEquipo'])->name('equipos.store');
    Route::put('/equipos/{equipo}', [AgendaController::class, 'guardarEquipo'])->whereNumber('equipo')->name('equipos.update');
    Route::delete('/equipos/{equipo}', [AgendaController::class, 'eliminarEquipo'])->whereNumber('equipo')->name('equipos.destroy');
});
