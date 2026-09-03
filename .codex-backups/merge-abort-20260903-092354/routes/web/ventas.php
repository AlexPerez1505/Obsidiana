<?php

use App\Http\Controllers\CobranzaController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panorama de cobranza (todas las ventas)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])
    ->get('/gestion-comercial/cobranza', [CobranzaController::class, 'index'])
    ->name('commercial.cobranza.index');

/*
|--------------------------------------------------------------------------
| Gestión Comercial · Ventas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-comercial/ventas')
    ->name('commercial.ventas.')
    ->group(function () {
        Route::get('/', [VentaController::class, 'index'])->name('index');
        Route::get('/crear', [VentaController::class, 'create'])->name('create');
        Route::post('/', [VentaController::class, 'store'])->name('store');

        Route::get('/{venta}', [VentaController::class, 'show'])->name('show');
        Route::get('/{venta}/editar', [VentaController::class, 'edit'])->name('edit');
        Route::put('/{venta}', [VentaController::class, 'update'])->name('update');
        Route::delete('/{venta}', [VentaController::class, 'destroy'])->name('destroy');
        Route::get('/{venta}/pdf', [VentaController::class, 'pdf'])->name('pdf');

        // Documentos que se entregan junto con el equipo
        Route::get('/{venta}/contrato', [VentaController::class, 'contrato'])->name('contrato');
        Route::get('/{venta}/carta-garantia', [VentaController::class, 'garantia'])->name('garantia');

        /*
        |------------------------------------------------------------------
        | Cobranza: lo que ya pagó el cliente y ajustes al calendario
        |------------------------------------------------------------------
        */
        Route::prefix('/{venta}/cobranza')->name('cobros.')->group(function () {
            Route::get('/', [CobroController::class, 'index'])->name('index');
            Route::post('/', [CobroController::class, 'store'])->name('store');
            Route::delete('/{cobro}', [CobroController::class, 'destroy'])->name('destroy');
            Route::get('/{cobro}/recibo', [CobroController::class, 'recibo'])->name('recibo');

            // Ajustes al plan
            Route::post('/recorrer', [CobroController::class, 'recorrer'])->name('recorrer');
            Route::post('/rebalancear', [CobroController::class, 'rebalancear'])->name('rebalancear');
            Route::post('/absorber-excedente', [CobroController::class, 'absorberExcedente'])->name('absorber-excedente');
            Route::post('/parcialidad', [CobroController::class, 'agregarParcialidad'])->name('parcialidad.agregar');
            Route::put('/parcialidad/{pago}', [CobroController::class, 'actualizarParcialidad'])->name('parcialidad.actualizar');
            Route::delete('/parcialidad/{pago}', [CobroController::class, 'eliminarParcialidad'])->name('parcialidad.eliminar');
        });
    });
