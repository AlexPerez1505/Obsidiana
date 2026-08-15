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
        Route::get('/', [FacturaController::class, 'index'])->name('index');
        Route::get('/crear', [FacturaController::class, 'create'])->name('create');
        Route::post('/', [FacturaController::class, 'store'])->name('store');

        Route::get('/{factura}', [FacturaController::class, 'show'])->name('show');
        Route::delete('/{factura}', [FacturaController::class, 'destroy'])->name('destroy');
        Route::get('/{factura}/pdf', [FacturaController::class, 'pdf'])->name('pdf');
    });
