<?php

use App\Http\Controllers\Api\V1\Ventas\CobranzaController;
use App\Http\Controllers\Api\V1\Ventas\CobroController;
use App\Http\Controllers\Api\V1\Ventas\ComisionController;
use App\Http\Controllers\Api\V1\Ventas\FacturaController;
use App\Http\Controllers\Api\V1\Ventas\ParcialidadController;
use App\Http\Controllers\Api\V1\Ventas\VentaController;
use App\Http\Controllers\Api\V1\Ventas\VentaRapidaController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — ventas. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Mismos permisos que las pantallas de routes/web/ventas.php y
| routes/web/facturas.php. El binding de {venta} ya regresa 403 si la
| venta es de otro asesor y el usuario no tiene ventas.ver_todas.
*/

/*
|--------------------------------------------------------------------------
| Ventas
|--------------------------------------------------------------------------
*/
Route::prefix('ventas')->name('ventas.')->group(function () {
    Route::get('/', [VentaController::class, 'index'])->middleware('can:ventas.ver')->name('index');
    Route::get('/formulario', [VentaController::class, 'formulario'])->middleware('can:ventas.crear')->name('formulario');
    Route::post('/', [VentaController::class, 'store'])->middleware('can:ventas.crear')->name('store');

    // Venta rápida (mostrador y congresos). Antes de /{venta}.
    Route::middleware('can:ventas.crear')->prefix('/rapida')->name('rapida.')->group(function () {
        Route::get('/formulario', [VentaRapidaController::class, 'formulario'])->name('formulario');
        Route::get('/pieza', [VentaRapidaController::class, 'pieza'])->name('pieza');
        Route::post('/', [VentaRapidaController::class, 'store'])->name('store');
    });

    Route::prefix('/{venta}')->whereNumber('venta')->group(function () {
        Route::get('/', [VentaController::class, 'show'])->middleware('can:ventas.ver')->name('show');
        Route::put('/', [VentaController::class, 'update'])->middleware('can:ventas.editar')->name('update');
        Route::delete('/', [VentaController::class, 'destroy'])->middleware('can:ventas.eliminar')->name('destroy');
        // Cancelar no borra: la venta queda como cancelada con su historial.
        Route::post('/cancelar', [VentaController::class, 'cancelar'])->middleware('can:ventas.editar')->name('cancelar');

        Route::get('/pdf', [VentaController::class, 'pdf'])->middleware('can:ventas.ver')->name('pdf');
        Route::get('/contrato', [VentaController::class, 'contrato'])->middleware('can:ventas.ver')->name('contrato');
        Route::get('/carta-garantia', [VentaController::class, 'garantia'])->middleware('can:ventas.ver')->name('garantia');

        // Cobros: lo que ya pagó el cliente.
        Route::prefix('/cobros')->name('cobros.')->group(function () {
            Route::get('/', [CobroController::class, 'index'])->middleware('can:cobranza.ver')->name('index');
            Route::post('/', [CobroController::class, 'store'])->middleware('can:cobranza.registrar')->name('store');
            Route::get('/{cobro}', [CobroController::class, 'show'])->whereNumber('cobro')->middleware('can:cobranza.ver')->name('show');
            Route::delete('/{cobro}', [CobroController::class, 'destroy'])->whereNumber('cobro')->middleware('can:cobranza.registrar')->name('destroy');
            Route::get('/{cobro}/recibo', [CobroController::class, 'recibo'])->whereNumber('cobro')->middleware('can:cobranza.ver')->name('recibo');
        });

        // Plan de pagos y sus ajustes.
        Route::prefix('/parcialidades')->name('parcialidades.')->group(function () {
            Route::get('/', [ParcialidadController::class, 'index'])->middleware('can:cobranza.ver')->name('index');

            Route::middleware('can:cobranza.ajustar')->group(function () {
                Route::post('/', [ParcialidadController::class, 'store'])->name('store');
                Route::post('/recorrer', [ParcialidadController::class, 'recorrer'])->name('recorrer');
                Route::post('/rebalancear', [ParcialidadController::class, 'rebalancear'])->name('rebalancear');
                Route::post('/absorber-excedente', [ParcialidadController::class, 'absorberExcedente'])->name('absorber-excedente');
                Route::put('/{parcialidad}', [ParcialidadController::class, 'update'])->whereNumber('parcialidad')->name('update');
                Route::delete('/{parcialidad}', [ParcialidadController::class, 'destroy'])->whereNumber('parcialidad')->name('destroy');
            });
        });
    });
});

/*
|--------------------------------------------------------------------------
| Panorama de cobranza (todas las ventas que el usuario puede ver)
|--------------------------------------------------------------------------
*/
Route::middleware('can:cobranza.ver')->prefix('cobranza')->name('cobranza.')->group(function () {
    Route::get('/', [CobranzaController::class, 'index'])->name('index');
    Route::get('/panorama', [CobranzaController::class, 'panorama'])->name('panorama');
});

/*
|--------------------------------------------------------------------------
| Comisiones
|--------------------------------------------------------------------------
*/
Route::middleware('can:comisiones.ver')->prefix('comisiones')->name('comisiones.')->group(function () {
    Route::get('/', [ComisionController::class, 'index'])->name('index');

    Route::middleware('can:comisiones.gestionar')->group(function () {
        Route::put('/asesores/{asesor}/porcentaje', [ComisionController::class, 'porcentaje'])->whereNumber('asesor')->name('porcentaje');
        Route::post('/pagos', [ComisionController::class, 'pagar'])->name('pagar');
        Route::delete('/pagos/{pago}', [ComisionController::class, 'eliminarPago'])->whereNumber('pago')->name('pagos.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Facturación (borradores)
|--------------------------------------------------------------------------
*/
Route::prefix('facturas')->name('facturas.')->group(function () {
    Route::get('/', [FacturaController::class, 'index'])->middleware('can:facturacion.ver')->name('index');
    Route::get('/formulario', [FacturaController::class, 'formulario'])->middleware('can:facturacion.crear')->name('formulario');
    Route::post('/', [FacturaController::class, 'store'])->middleware('can:facturacion.crear')->name('store');

    Route::get('/{factura}', [FacturaController::class, 'show'])->whereNumber('factura')->middleware('can:facturacion.ver')->name('show');
    Route::delete('/{factura}', [FacturaController::class, 'destroy'])->whereNumber('factura')->middleware('can:facturacion.crear')->name('destroy');
    Route::get('/{factura}/pdf', [FacturaController::class, 'pdf'])->whereNumber('factura')->middleware('can:facturacion.ver')->name('pdf');
});
