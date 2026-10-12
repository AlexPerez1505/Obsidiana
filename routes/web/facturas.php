<?php

use App\Http\Controllers\FacturaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión Comercial · Facturación (borradores)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-comercial/facturas')
    ->name('commercial.facturas.')
    ->group(function () {
        Route::get('/', [FacturaController::class, 'index'])->middleware('can:facturacion.ver')->name('index');
        Route::get('/crear', [FacturaController::class, 'create'])->middleware('can:facturacion.crear')->name('create');
        Route::post('/', [FacturaController::class, 'store'])->middleware('can:facturacion.crear')->name('store');

        Route::get('/{factura}', [FacturaController::class, 'show'])->middleware('can:facturacion.ver')->name('show');
        Route::delete('/{factura}', [FacturaController::class, 'destroy'])->middleware('can:facturacion.crear')->name('destroy');
        Route::get('/{factura}/pdf', [FacturaController::class, 'pdf'])->middleware('can:facturacion.ver')->name('pdf');
    });
