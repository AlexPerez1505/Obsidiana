<?php

use App\Http\Controllers\Garantias\CoberturaController;
use App\Http\Controllers\Garantias\GarantiaController;
use App\Http\Controllers\Garantias\ReclamacionController;
use App\Http\Controllers\Garantias\TableroController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Garantías · pólizas por equipo, reclamaciones, costos y recuperaciones
|--------------------------------------------------------------------------
| No hay rutas para borrar garantías ni bitácora: una póliza solo se anula,
| con motivo y permiso de aprobar.
*/
Route::middleware(['auth', 'verified', 'approved', 'can:garantias.ver'])
    ->prefix('garantias')
    ->name('garantias.')
    ->group(function () {
        Route::get('/', [TableroController::class, 'index'])->name('tablero');
        Route::get('/reportes', [TableroController::class, 'reportes'])->name('reportes');
        Route::get('/buscar', [TableroController::class, 'buscar'])->name('buscar');
        Route::get('/serie/{serie}', [TableroController::class, 'serie'])->where('serie', '.+')->name('serie');

        // Coberturas tipo
        Route::middleware('can:garantias.configurar')->group(function () {
            Route::get('/coberturas', [CoberturaController::class, 'index'])->name('coberturas.index');
            Route::post('/coberturas', [CoberturaController::class, 'store'])->name('coberturas.store');
            Route::put('/coberturas/{cobertura}', [CoberturaController::class, 'update'])->name('coberturas.update');
            Route::post('/sincronizar', [GarantiaController::class, 'sincronizar'])->name('sincronizar');
        });

        // Reclamaciones (antes de /{garantia} para que "reclamaciones" no se tome como id)
        Route::prefix('reclamaciones')->name('reclamaciones.')->group(function () {
            Route::get('/', [ReclamacionController::class, 'index'])->name('index');
            Route::get('/buscar-serie', [ReclamacionController::class, 'buscarSerie'])->name('buscar-serie');
            Route::get('/{reclamacion}/documento/{tipo}', [ReclamacionController::class, 'documento'])->name('documento');

            Route::middleware('can:garantias.gestionar')->group(function () {
                Route::get('/nueva', [ReclamacionController::class, 'create'])->name('create');
                Route::post('/', [ReclamacionController::class, 'store'])->name('store');
                Route::put('/{reclamacion}', [ReclamacionController::class, 'update'])->name('update');
                Route::post('/{reclamacion}/vincular', [ReclamacionController::class, 'vincular'])->name('vincular');
                Route::post('/{reclamacion}/etapa', [ReclamacionController::class, 'etapa'])->name('etapa');
                Route::post('/{reclamacion}/diagnostico', [ReclamacionController::class, 'diagnostico'])->name('diagnostico');
                Route::post('/{reclamacion}/costos', [ReclamacionController::class, 'costo'])->name('costo');
                Route::post('/costos/{costo}/recuperado', [ReclamacionController::class, 'recuperado'])->name('costo.recuperado');
                Route::post('/{reclamacion}/sustituciones', [ReclamacionController::class, 'sustitucion'])->name('sustitucion');
                Route::post('/sustituciones/{sustitucion}/devuelto', [ReclamacionController::class, 'devuelto'])->name('sustitucion.devuelto');
                Route::post('/{reclamacion}/servicio', [ReclamacionController::class, 'servicio'])->name('servicio');
                Route::post('/{reclamacion}/notas', [ReclamacionController::class, 'nota'])->name('nota');
                Route::post('/{reclamacion}/archivos', [ReclamacionController::class, 'archivo'])->name('archivo');
                Route::post('/{reclamacion}/cerrar', [ReclamacionController::class, 'cerrar'])->name('cerrar');
            });

            Route::post('/{reclamacion}/dictamen', [ReclamacionController::class, 'dictamen'])
                ->middleware('can:garantias.dictaminar')->name('dictamen');
            Route::post('/costos/{costo}/resolver', [ReclamacionController::class, 'resolverCosto'])
                ->middleware('can:garantias.aprobar')->name('costo.resolver');

            Route::get('/{reclamacion}', [ReclamacionController::class, 'show'])->name('show');
        });

        // Expedientes
        Route::get('/expedientes', [GarantiaController::class, 'index'])->name('index');
        Route::middleware('can:garantias.gestionar')->group(function () {
            Route::get('/expedientes/nueva', [GarantiaController::class, 'create'])->name('create');
            Route::post('/expedientes', [GarantiaController::class, 'store'])->name('store');
            Route::put('/expedientes/{garantia}', [GarantiaController::class, 'update'])->name('update');
            Route::post('/expedientes/{garantia}/archivos', [GarantiaController::class, 'archivo'])->name('archivo');
        });
        Route::middleware('can:garantias.aprobar')->group(function () {
            Route::post('/expedientes/{garantia}/fechas', [GarantiaController::class, 'fechas'])->name('fechas');
            Route::post('/expedientes/{garantia}/anular', [GarantiaController::class, 'anular'])->name('anular');
            Route::post('/expedientes/{garantia}/reactivar', [GarantiaController::class, 'reactivar'])->name('reactivar');
        });
        Route::get('/expedientes/{garantia}/poliza', [GarantiaController::class, 'poliza'])->name('poliza');
        Route::get('/expedientes/{garantia}', [GarantiaController::class, 'show'])->name('show');
    });
