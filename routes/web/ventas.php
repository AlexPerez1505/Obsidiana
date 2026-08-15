<?php

use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

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
    });
