<?php

use App\Http\Controllers\Commercial\CustomerController;
use App\Http\Controllers\Commercial\SeguimientoController;
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

    // Va antes de {cliente} para que "similar" no se tome como un id.
    Route::get('/gestion-comercial/clientes/similar', [CustomerController::class, 'similar'])
        ->name('commercial.clientes.similar');

    Route::get('/gestion-comercial/clientes/{cliente}', [CustomerController::class, 'show'])
        ->name('commercial.clientes.show');
        

    Route::get('/gestion-comercial/clientes/{cliente}/editar', [CustomerController::class, 'edit'])
        ->name('commercial.clientes.edit');

    Route::put('/gestion-comercial/clientes/{cliente}', [CustomerController::class, 'update'])
        ->name('commercial.clientes.update');

    Route::post('/gestion-comercial/clientes/categorias', [CustomerController::class, 'storeCategory'])
        ->name('commercial.clientes.categories.store');

    // Seguimientos de clientes y prospectos (recordatorios)
    Route::post('/gestion-comercial/clientes/{cliente}/seguimientos', [SeguimientoController::class, 'store'])
        ->name('commercial.clientes.seguimientos.store');
    Route::post('/gestion-comercial/clientes/{cliente}/seguimientos/{seguimiento}/hecho', [SeguimientoController::class, 'hecho'])
        ->name('commercial.clientes.seguimientos.hecho');
    Route::post('/gestion-comercial/clientes/{cliente}/seguimientos/{seguimiento}/reprogramar', [SeguimientoController::class, 'reprogramar'])
        ->name('commercial.clientes.seguimientos.reprogramar');
    Route::delete('/gestion-comercial/clientes/{cliente}/seguimientos/{seguimiento}', [SeguimientoController::class, 'destroy'])
        ->name('commercial.clientes.seguimientos.destroy');
    Route::post('/gestion-comercial/clientes/{cliente}/convertir', [SeguimientoController::class, 'convertir'])
        ->name('commercial.clientes.convertir');

    // Rutas heredadas: el apartado de planes de pago ya no existe como modulo.
    Route::redirect('/gestion-comercial/planes-pago', '/gestion-comercial/cotizaciones');
    Route::redirect('/gestion-comercial/planes-pago/crear', '/gestion-comercial/cotizaciones/crear');
    Route::redirect('/gestion-comercial/planes-pago/{planPago}/editar', '/gestion-comercial/cotizaciones');

    // Cotizaciones
    // NOTA: el CRUD de cotizaciones vive en routes/web/cotizaciones.php.
    // Aqui solo quedan los endpoints que ese modulo no cubre.
    Route::get('/gestion-comercial/cotizaciones/buscar-cliente', [CotizacionController::class, 'buscarCliente'])
        ->name('commercial.cotizaciones.buscarCliente');

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
