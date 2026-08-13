<?php

use App\Http\Controllers\Commercial\CustomerController;
use App\Http\Controllers\Commercial\PromocionController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\PlanPagoPlantillaController;
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

    // Planes de pago (plantillas reutilizables)
    Route::get('/gestion-comercial/planes-pago', [PlanPagoPlantillaController::class, 'index'])
        ->name('commercial.planesPago.index');

    Route::get('/gestion-comercial/planes-pago/crear', [PlanPagoPlantillaController::class, 'create'])
        ->name('commercial.planesPago.create');

    Route::post('/gestion-comercial/planes-pago', [PlanPagoPlantillaController::class, 'store'])
        ->name('commercial.planesPago.store');

    Route::get('/gestion-comercial/planes-pago/{planPago}/editar', [PlanPagoPlantillaController::class, 'edit'])
        ->name('commercial.planesPago.edit');

    Route::put('/gestion-comercial/planes-pago/{planPago}', [PlanPagoPlantillaController::class, 'update'])
        ->name('commercial.planesPago.update');

    Route::delete('/gestion-comercial/planes-pago/{planPago}', [PlanPagoPlantillaController::class, 'destroy'])
        ->name('commercial.planesPago.destroy');

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

    Route::post('/gestion-comercial/cotizaciones/{cotizacion}/plan-pagos', [CotizacionController::class, 'storePlanPago'])
        ->name('commercial.cotizaciones.planPagos.store');

    Route::post('/gestion-comercial/cotizaciones/plan-pagos/{planPago}/pagos', [CotizacionController::class, 'storePago'])
        ->name('commercial.cotizaciones.pagos.store');

    // Remisiones (cotizaciones convertidas en venta definitiva)
    Route::get('/gestion-comercial/remisiones', [CotizacionController::class, 'remisiones'])
        ->name('commercial.remisiones.index');

    // Promociones (WhatsApp)
    Route::get('/gestion-comercial/promociones', [PromocionController::class, 'index'])
        ->name('commercial.promociones.index');

    Route::get('/gestion-comercial/promociones/crear', [PromocionController::class, 'create'])
        ->name('commercial.promociones.create');

    Route::post('/gestion-comercial/promociones', [PromocionController::class, 'store'])
        ->name('commercial.promociones.store');

    Route::get('/gestion-comercial/promociones/{promocion}', [PromocionController::class, 'show'])
        ->name('commercial.promociones.show');

    Route::post('/gestion-comercial/promociones/{promocion}/enviar', [PromocionController::class, 'send'])
        ->name('commercial.promociones.send');

    Route::delete('/gestion-comercial/promociones/{promocion}', [PromocionController::class, 'destroy'])
        ->name('commercial.promociones.destroy');
});
