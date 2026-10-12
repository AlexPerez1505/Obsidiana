<?php

use App\Http\Controllers\Api\V1\Logistica\ChoferController;
use App\Http\Controllers\Api\V1\Logistica\FlotaController;
use App\Http\Controllers\Api\V1\Logistica\IncidenciaController;
use App\Http\Controllers\Api\V1\Logistica\IndicadoresController;
use App\Http\Controllers\Api\V1\Logistica\MapaController;
use App\Http\Controllers\Api\V1\Logistica\RutaController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — logistica. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Mismos permisos que routes/web/logistica.php. Incidencias e indicadores
| llevan /logistica delante porque "incidencias" también existe en
| asistencia (RH) y sin el prefijo no se sabría de cuáles se habla.
*/

// Mapa en vivo de la flota
Route::get('/mapa', [MapaController::class, 'index'])->middleware('can:rutas.mapa')->name('mapa');

// La app del chofer: solo rutas asignadas a quien llama
Route::middleware('can:rutas.chofer')->prefix('chofer/rutas')->name('chofer.rutas.')->group(function () {
    Route::get('/', [ChoferController::class, 'index'])->name('index');
    Route::get('/{ruta}', [ChoferController::class, 'show'])->whereNumber('ruta')->name('show');
    Route::post('/{ruta}/ubicacion', [ChoferController::class, 'ubicacion'])->whereNumber('ruta')->name('ubicacion');
    Route::post('/{ruta}/paradas/{parada}/llegada', [ChoferController::class, 'llegada'])->whereNumber(['ruta', 'parada'])->name('llegada');
    Route::post('/{ruta}/paradas/{parada}/completar', [ChoferController::class, 'completar'])->whereNumber(['ruta', 'parada'])->name('completar');
    Route::post('/{ruta}/paradas/{parada}/no-entregada', [ChoferController::class, 'noEntregada'])->whereNumber(['ruta', 'parada'])->name('no-entregada');
    Route::post('/{ruta}/incidencias', [ChoferController::class, 'incidencia'])->whereNumber('ruta')->name('incidencia');
    Route::post('/{ruta}/combustible', [ChoferController::class, 'combustible'])->whereNumber('ruta')->name('combustible');
});

// Incidencias reportadas en ruta
Route::middleware('can:rutas.incidencias')->prefix('logistica/incidencias')->name('logistica.incidencias.')->group(function () {
    Route::get('/', [IncidenciaController::class, 'index'])->name('index');
    Route::get('/{incidencia}', [IncidenciaController::class, 'show'])->whereNumber('incidencia')->name('show');
    Route::post('/{incidencia}/resolver', [IncidenciaController::class, 'resolver'])->whereNumber('incidencia')->name('resolver');
});

// Flota: combustible, kilometraje y mantenimiento
Route::middleware('can:rutas.flota')->prefix('flota')->name('flota.')->group(function () {
    Route::get('/', [FlotaController::class, 'index'])->name('index');
    Route::get('/{vehicle}', [FlotaController::class, 'show'])->whereNumber('vehicle')->name('show');
    Route::get('/{vehicle}/combustible', [FlotaController::class, 'combustibles'])->whereNumber('vehicle')->name('combustible.index');
    Route::post('/{vehicle}/combustible', [FlotaController::class, 'guardarCombustible'])->whereNumber('vehicle')->name('combustible.store');
    Route::delete('/combustible/{carga}', [FlotaController::class, 'eliminarCombustible'])->whereNumber('carga')->name('combustible.destroy');
    Route::get('/{vehicle}/mantenimiento', [FlotaController::class, 'mantenimientos'])->whereNumber('vehicle')->name('mantenimiento.index');
    Route::post('/{vehicle}/mantenimiento', [FlotaController::class, 'guardarMantenimiento'])->whereNumber('vehicle')->name('mantenimiento.store');
    Route::delete('/mantenimiento/{mantenimiento}', [FlotaController::class, 'eliminarMantenimiento'])->whereNumber('mantenimiento')->name('mantenimiento.destroy');
});

Route::get('/logistica/indicadores', [IndicadoresController::class, 'index'])->middleware('can:rutas.analytics')->name('logistica.indicadores');

// Administración de rutas
Route::prefix('rutas')->name('rutas.')->group(function () {
    Route::get('/', [RutaController::class, 'index'])->middleware('can:rutas.ver')->name('index');
    Route::post('/', [RutaController::class, 'store'])->middleware('can:rutas.crear')->name('store');
    // Datos del formulario (en la web: rutas/crear y rutas/{ruta}/editar); pide crear o editar
    Route::get('/catalogos', [RutaController::class, 'catalogos'])->name('catalogos');
    Route::get('/ordenes-listas', [RutaController::class, 'ordenesListas'])->middleware('can:rutas.crear')->name('ordenes-listas');
    Route::get('/geocodificar', [RutaController::class, 'geocodificar'])->middleware('can:rutas.crear')->name('geocodificar');

    Route::whereNumber('ruta')->group(function () {
        Route::get('/{ruta}', [RutaController::class, 'show'])->middleware('can:rutas.ver')->name('show');
        Route::get('/{ruta}/en-vivo', [RutaController::class, 'enVivo'])->middleware('can:rutas.ver')->name('en-vivo');
        Route::put('/{ruta}', [RutaController::class, 'update'])->middleware('can:rutas.editar')->name('update');
        Route::delete('/{ruta}', [RutaController::class, 'destroy'])->middleware('can:rutas.eliminar')->name('destroy');

        Route::post('/{ruta}/optimizar', [RutaController::class, 'optimizar'])->middleware('can:rutas.crear')->name('optimizar');
        Route::post('/{ruta}/iniciar', [RutaController::class, 'iniciar'])->middleware('can:rutas.operar')->name('iniciar');
        Route::post('/{ruta}/finalizar', [RutaController::class, 'finalizar'])->middleware('can:rutas.operar')->name('finalizar');
        Route::post('/{ruta}/cancelar', [RutaController::class, 'cancelar'])->middleware('can:rutas.operar')->name('cancelar');
    });
});
