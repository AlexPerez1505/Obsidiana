<?php

use App\Http\Controllers\Configuracion\CatalogoEquipoController;
use App\Http\Controllers\Configuracion\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catálogo (categorías, tipos de equipo, etc.)
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
