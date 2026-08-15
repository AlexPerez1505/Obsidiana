<?php

use App\Http\Controllers\Commercial\CustomerController;
use App\Http\Controllers\CotizacionController;
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

    // Rutas heredadas: el apartado de planes de pago ya no existe como modulo.
    Route::redirect('/gestion-comercial/planes-pago', '/gestion-comercial/cotizaciones');
    Route::redirect('/gestion-comercial/planes-pago/crear', '/gestion-comercial/cotizaciones/crear');
    Route::redirect('/gestion-comercial/planes-pago/{planPago}/editar', '/gestion-comercial/cotizaciones');

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

    Route::get('/gestion-comercial/cotizaciones/{cotizacion}/editar', [CotizacionController::class, 'edit'])
        ->name('commercial.cotizaciones.edit');

    Route::put('/gestion-comercial/cotizaciones/{cotizacion}', [CotizacionController::class, 'update'])
        ->name('commercial.cotizaciones.update');

    Route::post('/gestion-comercial/cotizaciones/{cotizacion}/remision', [CotizacionController::class, 'convertirRemision'])
        ->name('commercial.cotizaciones.remision');

    Route::post('/gestion-comercial/cotizaciones/plan-pagos/{planPago}/pagos', [CotizacionController::class, 'storePago'])
        ->name('commercial.cotizaciones.pagos.store');

    // Remisiones (cotizaciones convertidas en venta definitiva)
    Route::get('/gestion-comercial/remisiones', [CotizacionController::class, 'remisiones'])
        ->name('commercial.remisiones.index');

    Route::get('/gestion-comercial/remisiones/{cotizacion}/pdf', [CotizacionController::class, 'descargarRemisionPdf'])
        ->name('commercial.remisiones.pdf');

    Route::get('/gestion-comercial/promociones', function () {
        return view('structure.commercial_management.placeholder', ['titulo' => 'Promociones']);
    })->name('commercial.promociones.index');
});
