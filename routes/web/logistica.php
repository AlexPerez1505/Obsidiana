<?php

use App\Http\Controllers\Logistica\AnalyticsController;
use App\Http\Controllers\Logistica\ChoferController;
use App\Http\Controllers\Logistica\FlotaController;
use App\Http\Controllers\Logistica\IncidenciaController;
use App\Http\Controllers\Logistica\MapaController;
use App\Http\Controllers\Logistica\RutaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Logística · rutas de reparto, mapa en vivo y app del chofer
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('logistica')
    ->name('logistica.')
    ->group(function () {
        // Mapa en vivo de la flota
        Route::middleware('can:rutas.mapa')->group(function () {
            Route::get('/mapa', [MapaController::class, 'index'])->name('mapa');
            Route::get('/mapa/feed', [MapaController::class, 'feed'])->name('mapa.feed');
        });

        // Parte del chofer (antes de /rutas/{ruta} para que "chofer" no se tome como folio)
        Route::middleware('can:rutas.chofer')->prefix('chofer')->name('chofer.')->group(function () {
            Route::get('/', [ChoferController::class, 'index'])->name('index');
            Route::get('/rutas/{ruta}', [ChoferController::class, 'show'])->name('show');
            Route::post('/rutas/{ruta}/ubicacion', [ChoferController::class, 'ubicacion'])->name('ubicacion');
            Route::post('/rutas/{ruta}/paradas/{parada}/llegada', [ChoferController::class, 'llegada'])->name('llegada');
            Route::post('/rutas/{ruta}/paradas/{parada}/completar', [ChoferController::class, 'completar'])->name('completar');
            Route::post('/rutas/{ruta}/paradas/{parada}/no-entregada', [ChoferController::class, 'noEntregada'])->name('no-entregada');
            Route::post('/rutas/{ruta}/incidencias', [ChoferController::class, 'incidencia'])->name('incidencia');
            Route::post('/rutas/{ruta}/combustible', [ChoferController::class, 'combustible'])->name('combustible');
        });

        // Incidencias reportadas en ruta
        Route::middleware('can:rutas.incidencias')->prefix('incidencias')->name('incidencias.')->group(function () {
            Route::get('/', [IncidenciaController::class, 'index'])->name('index');
            Route::post('/{incidencia}/resolver', [IncidenciaController::class, 'resolver'])->name('resolver');
        });

        // Flota: combustible, kilometraje y mantenimiento
        Route::middleware('can:rutas.flota')->prefix('flota')->name('flota.')->group(function () {
            Route::get('/', [FlotaController::class, 'index'])->name('index');
            Route::get('/{vehicle}', [FlotaController::class, 'show'])->name('show');
            Route::post('/{vehicle}/combustible', [FlotaController::class, 'guardarCombustible'])->name('combustible.store');
            Route::delete('/combustible/{carga}', [FlotaController::class, 'eliminarCombustible'])->name('combustible.destroy');
            Route::post('/{vehicle}/mantenimiento', [FlotaController::class, 'guardarMantenimiento'])->name('mantenimiento.store');
            Route::delete('/mantenimiento/{mantenimiento}', [FlotaController::class, 'eliminarMantenimiento'])->name('mantenimiento.destroy');
        });

        Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('can:rutas.analytics')->name('analytics');

        // Administración de rutas
        Route::prefix('rutas')->name('rutas.')->group(function () {
            Route::get('/', [RutaController::class, 'index'])->middleware('can:rutas.ver')->name('index');
            Route::get('/crear', [RutaController::class, 'create'])->middleware('can:rutas.crear')->name('create');
            Route::post('/', [RutaController::class, 'store'])->middleware('can:rutas.crear')->name('store');
            Route::get('/geocodificar', [RutaController::class, 'geocodificar'])->middleware('can:rutas.crear')->name('geocodificar');

            Route::get('/{ruta}', [RutaController::class, 'show'])->middleware('can:rutas.ver')->name('show');
            Route::get('/{ruta}/feed', [RutaController::class, 'feed'])->middleware('can:rutas.ver')->name('feed');
            Route::get('/{ruta}/editar', [RutaController::class, 'edit'])->middleware('can:rutas.editar')->name('edit');
            Route::put('/{ruta}', [RutaController::class, 'update'])->middleware('can:rutas.editar')->name('update');
            Route::delete('/{ruta}', [RutaController::class, 'destroy'])->middleware('can:rutas.eliminar')->name('destroy');

            Route::post('/{ruta}/optimizar', [RutaController::class, 'optimizar'])->middleware('can:rutas.crear')->name('optimizar');
            Route::post('/{ruta}/iniciar', [RutaController::class, 'iniciar'])->middleware('can:rutas.operar')->name('iniciar');
            Route::post('/{ruta}/finalizar', [RutaController::class, 'finalizar'])->middleware('can:rutas.operar')->name('finalizar');
            Route::post('/{ruta}/cancelar', [RutaController::class, 'cancelar'])->middleware('can:rutas.operar')->name('cancelar');
        });
    });
