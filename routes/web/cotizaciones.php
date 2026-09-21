<?php

use App\Http\Controllers\CotizacionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión Comercial · Cotizaciones
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-comercial/cotizaciones')
    ->name('commercial.cotizaciones.')
    ->group(function () {
        // Endpoints JSON (antes del binding {cotizacion})
        Route::get('/buscar/clientes', [CotizacionController::class, 'buscarClientes'])->name('clientes.buscar');
        Route::get('/buscar/productos', [CotizacionController::class, 'buscarProductos'])->name('productos.buscar');
        Route::get('/buscar/fichas', [CotizacionController::class, 'buscarFichas'])->name('fichas.buscar');

        Route::get('/', [CotizacionController::class, 'index'])->middleware('can:cotizaciones.ver')->name('index');
        Route::get('/crear', [CotizacionController::class, 'create'])->middleware('can:cotizaciones.crear')->name('create');
        Route::post('/', [CotizacionController::class, 'store'])->middleware('can:cotizaciones.crear')->name('store');

        Route::get('/{cotizacion}', [CotizacionController::class, 'show'])->middleware('can:cotizaciones.ver')->name('show');
        Route::get('/{cotizacion}/editar', [CotizacionController::class, 'edit'])->middleware('can:cotizaciones.editar')->name('edit');
        Route::put('/{cotizacion}', [CotizacionController::class, 'update'])->middleware('can:cotizaciones.editar')->name('update');
        Route::delete('/{cotizacion}', [CotizacionController::class, 'destroy'])->middleware('can:cotizaciones.eliminar')->name('destroy');
        Route::get('/{cotizacion}/pdf', [CotizacionController::class, 'pdf'])->middleware('can:cotizaciones.ver')->name('pdf');
    });
