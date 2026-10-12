<?php

use App\Http\Controllers\CobranzaController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\ComisionController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaRapidaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panorama de cobranza (todas las ventas)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved', 'can:cobranza.ver'])
    ->get('/gestion-comercial/cobranza', [CobranzaController::class, 'index'])
    ->name('commercial.cobranza.index');

/*
|--------------------------------------------------------------------------
| Comisiones: quién vendió más y cuánto le toca
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved', 'can:comisiones.ver'])
    ->prefix('/gestion-comercial/comisiones')
    ->name('commercial.comisiones.')
    ->group(function () {
        Route::get('/', [ComisionController::class, 'index'])->name('index');

        Route::middleware('can:comisiones.gestionar')->group(function () {
            Route::put('/asesor/{asesor}/porcentaje', [ComisionController::class, 'porcentaje'])->name('porcentaje');
            Route::post('/pagos', [ComisionController::class, 'pagar'])->name('pagar');
            Route::delete('/pagos/{pago}', [ComisionController::class, 'eliminarPago'])->name('pagos.destroy');
        });
    });

/*
|--------------------------------------------------------------------------
| Gestión Comercial · Ventas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-comercial/ventas')
    ->name('commercial.ventas.')
    ->group(function () {
        Route::get('/', [VentaController::class, 'index'])->middleware('can:ventas.ver')->name('index');
        Route::get('/crear', [VentaController::class, 'create'])->middleware('can:ventas.crear')->name('create');
        Route::post('/', [VentaController::class, 'store'])->middleware('can:ventas.crear')->name('store');

        /*
        |------------------------------------------------------------------
        | Venta rápida: escanear con el celular y cobrar al público en
        | general (mostrador y congresos). Va antes de /{venta} para que
        | "rapida" no se tome como folio.
        |------------------------------------------------------------------
        */
        Route::middleware('can:ventas.crear')->prefix('/rapida')->name('rapida.')->group(function () {
            Route::get('/', [VentaRapidaController::class, 'index'])->name('index');
            Route::post('/pieza', [VentaRapidaController::class, 'pieza'])->name('pieza');
            Route::post('/', [VentaRapidaController::class, 'store'])->name('store');
        });

        Route::get('/{venta}', [VentaController::class, 'show'])->middleware('can:ventas.ver')->name('show');
        Route::get('/{venta}/editar', [VentaController::class, 'edit'])->middleware('can:ventas.editar')->name('edit');
        Route::put('/{venta}', [VentaController::class, 'update'])->middleware('can:ventas.editar')->name('update');
        Route::delete('/{venta}', [VentaController::class, 'destroy'])->middleware('can:ventas.eliminar')->name('destroy');
        // Cancelar no borra: la venta queda como cancelada con su historial.
        Route::post('/{venta}/cancelar', [VentaController::class, 'cancelar'])->middleware('can:ventas.editar')->name('cancelar');
        Route::get('/{venta}/pdf', [VentaController::class, 'pdf'])->middleware('can:ventas.ver')->name('pdf');

        // Documentos que se entregan junto con el equipo
        Route::get('/{venta}/contrato', [VentaController::class, 'contrato'])->middleware('can:ventas.ver')->name('contrato');
        Route::get('/{venta}/carta-garantia', [VentaController::class, 'garantia'])->middleware('can:ventas.ver')->name('garantia');

        /*
        |------------------------------------------------------------------
        | Cobranza: lo que ya pagó el cliente y ajustes al calendario
        |------------------------------------------------------------------
        */
        Route::prefix('/{venta}/cobranza')->name('cobros.')->group(function () {
            Route::get('/', [CobroController::class, 'index'])->middleware('can:cobranza.ver')->name('index');
            Route::post('/', [CobroController::class, 'store'])->middleware('can:cobranza.registrar')->name('store');
            Route::delete('/{cobro}', [CobroController::class, 'destroy'])->middleware('can:cobranza.registrar')->name('destroy');
            Route::get('/{cobro}/recibo', [CobroController::class, 'recibo'])->middleware('can:cobranza.ver')->name('recibo');

            // Ajustes al plan
            Route::post('/recorrer', [CobroController::class, 'recorrer'])->middleware('can:cobranza.ajustar')->name('recorrer');
            Route::post('/rebalancear', [CobroController::class, 'rebalancear'])->middleware('can:cobranza.ajustar')->name('rebalancear');
            Route::post('/absorber-excedente', [CobroController::class, 'absorberExcedente'])->middleware('can:cobranza.ajustar')->name('absorber-excedente');
            Route::post('/parcialidad', [CobroController::class, 'agregarParcialidad'])->middleware('can:cobranza.ajustar')->name('parcialidad.agregar');
            Route::put('/parcialidad/{pago}', [CobroController::class, 'actualizarParcialidad'])->middleware('can:cobranza.ajustar')->name('parcialidad.actualizar');
            Route::delete('/parcialidad/{pago}', [CobroController::class, 'eliminarParcialidad'])->middleware('can:cobranza.ajustar')->name('parcialidad.eliminar');
        });
    });
