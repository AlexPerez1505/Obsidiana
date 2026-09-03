<?php

use App\Http\Controllers\Configuracion\CatalogoEquipoController;
use App\Http\Controllers\Configuracion\CategoryController;
use App\Http\Controllers\Configuracion\CongressController;
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

    /*
    |----------------------------------------------------------------------
    | Catálogo de equipo: tipo → subtipo → marca → modelo
    |----------------------------------------------------------------------
    */
    Route::prefix('configuracion/catalogo-equipo')
        ->name('configuracion.catalogo_equipo.')
        ->group(function () {
            // Endpoints JSON de la cascada (antes de las rutas con parámetro)
            Route::get('/subtipos', [CatalogoEquipoController::class, 'subtypes'])->name('subtipos');
            Route::get('/marcas', [CatalogoEquipoController::class, 'brands'])->name('marcas');
            Route::get('/modelos', [CatalogoEquipoController::class, 'models'])->name('modelos');

            Route::post('/tipos', [CatalogoEquipoController::class, 'storeType'])->name('tipos.store');
            Route::put('/tipos/{type}', [CatalogoEquipoController::class, 'updateType'])->name('tipos.update');
            Route::delete('/tipos/{type}', [CatalogoEquipoController::class, 'destroyType'])->name('tipos.destroy');

            Route::post('/subtipos', [CatalogoEquipoController::class, 'storeSubtype'])->name('subtipos.store');
            Route::put('/subtipos/{subtype}', [CatalogoEquipoController::class, 'updateSubtype'])->name('subtipos.update');
            Route::delete('/subtipos/{subtype}', [CatalogoEquipoController::class, 'destroySubtype'])->name('subtipos.destroy');

            Route::post('/marcas', [CatalogoEquipoController::class, 'storeBrand'])->name('marcas.store');
            Route::put('/marcas/{brand}', [CatalogoEquipoController::class, 'updateBrand'])->name('marcas.update');
            Route::delete('/marcas/{brand}', [CatalogoEquipoController::class, 'destroyBrand'])->name('marcas.destroy');

            Route::post('/modelos', [CatalogoEquipoController::class, 'storeModel'])->name('modelos.store');
            Route::put('/modelos/{model}', [CatalogoEquipoController::class, 'updateModel'])->name('modelos.update');
            Route::delete('/modelos/{model}', [CatalogoEquipoController::class, 'destroyModel'])->name('modelos.destroy');
        });
});
