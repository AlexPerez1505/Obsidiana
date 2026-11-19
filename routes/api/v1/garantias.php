<?php

use App\Http\Controllers\Api\V1\Garantias\CentroController;
use App\Http\Controllers\Api\V1\Garantias\CoberturaController;
use App\Http\Controllers\Api\V1\Garantias\ExpedienteController;
use App\Http\Controllers\Api\V1\Garantias\ReclamacionController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — garantías. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Mismos permisos que routes/web/garantias.php. Las rutas fijas van antes
| de {reclamacion} y {garantia} para que "catalogos" o "buscar-serie" no
| se tomen como un id.
*/
Route::prefix('garantias')->name('garantias.')->middleware('can:garantias.ver')->group(function () {
    Route::get('/centro', [CentroController::class, 'centro'])->name('centro');
    Route::get('/reportes', [CentroController::class, 'reportes'])->name('reportes');

    Route::get('/expedientes/catalogos', [ExpedienteController::class, 'catalogos'])->name('expedientes.catalogos');
    Route::get('/expedientes', [ExpedienteController::class, 'index'])->name('expedientes.index');
    Route::get('/expedientes/{garantia}', [ExpedienteController::class, 'show'])
        ->whereNumber('garantia')->name('expedientes.show');
    Route::post('/expedientes', [ExpedienteController::class, 'store'])
        ->middleware('can:garantias.gestionar')->name('expedientes.store');

    Route::get('/reclamaciones/catalogos', [ReclamacionController::class, 'catalogos'])->name('reclamaciones.catalogos');
    Route::get('/reclamaciones/buscar-serie', [ReclamacionController::class, 'buscarSerie'])->name('reclamaciones.buscar-serie');
    Route::get('/reclamaciones', [ReclamacionController::class, 'index'])->name('reclamaciones.index');
    Route::post('/reclamaciones', [ReclamacionController::class, 'store'])
        ->middleware('can:garantias.gestionar')->name('reclamaciones.store');
    Route::get('/reclamaciones/{reclamacion}', [ReclamacionController::class, 'show'])
        ->whereNumber('reclamacion')->name('reclamaciones.show');
    Route::put('/reclamaciones/{reclamacion}', [ReclamacionController::class, 'update'])
        ->whereNumber('reclamacion')->middleware('can:garantias.gestionar')->name('reclamaciones.update');
    Route::post('/reclamaciones/{reclamacion}/etapa', [ReclamacionController::class, 'etapa'])
        ->whereNumber('reclamacion')->middleware('can:garantias.gestionar')->name('reclamaciones.etapa');

    Route::get('/coberturas', [CoberturaController::class, 'index'])->name('coberturas.index');
    Route::post('/coberturas', [CoberturaController::class, 'store'])
        ->middleware('can:garantias.configurar')->name('coberturas.store');
    Route::put('/coberturas/{cobertura}', [CoberturaController::class, 'update'])
        ->whereNumber('cobertura')->middleware('can:garantias.configurar')->name('coberturas.update');
});
