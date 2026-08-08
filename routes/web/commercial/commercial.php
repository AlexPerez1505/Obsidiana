<?php

use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión Comercial
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-comercial/clientes', [CustomerController::class, 'index'])
        ->name('commercial.clientes.index');

    Route::get('/gestion-comercial/clientes/registrar', [CustomerController::class, 'create'])
        ->name('commercial.clientes.create');

    Route::post('/gestion-comercial/clientes/registrar', [CustomerController::class, 'store'])
        ->name('commercial.clientes.store');

    Route::get('/gestion-comercial/clientes/{cliente}', [CustomerController::class, 'show'])
        ->name('commercial.clientes.show');

    Route::get('/gestion-comercial/clientes/{cliente}/editar', [CustomerController::class, 'edit'])
        ->name('commercial.clientes.edit');

    Route::put('/gestion-comercial/clientes/{cliente}', [CustomerController::class, 'update'])
        ->name('commercial.clientes.update');

    Route::post('/gestion-comercial/clientes/categorias', [CustomerController::class, 'storeCategory'])
        ->name('commercial.clientes.categories.store');

    // Cotizaciones
    Route::get('/gestion-comercial/cotizaciones', [CotizacionController::class, 'index'])
        ->name('commercial.cotizaciones.index');

    Route::get('/gestion-comercial/cotizaciones/buscar-cliente', [CotizacionController::class, 'buscarCliente'])
        ->name('commercial.cotizaciones.buscarCliente');

    Route::get('/gestion-comercial/cotizaciones/crear', [CotizacionController::class, 'create'])
        ->name('commercial.cotizaciones.create');

    Route::post('/gestion-comercial/cotizaciones', [CotizacionController::class, 'store'])
        ->name('commercial.cotizaciones.store');

    Route::get('/gestion-comercial/cotizaciones/{cotizacion}', [CotizacionController::class, 'show'])
        ->name('commercial.cotizaciones.show');

    Route::post('/gestion-comercial/cotizaciones/{cotizacion}/plan-pagos', [CotizacionController::class, 'storePlanPago'])
        ->name('commercial.cotizaciones.planPagos.store');

    Route::post('/gestion-comercial/cotizaciones/plan-pagos/{planPago}/pagos', [CotizacionController::class, 'storePago'])
        ->name('commercial.cotizaciones.pagos.store');

    // Remisiones y Promociones (próximamente)
    Route::get('/gestion-comercial/remisiones', function () {
        return view('structure.commercial_management.placeholder', ['titulo' => 'Remisiones']);
    })->name('commercial.remisiones.index');

    Route::get('/gestion-comercial/promociones', function () {
        return view('structure.commercial_management.placeholder', ['titulo' => 'Promociones']);
    })->name('commercial.promociones.index');
});
