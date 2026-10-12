<?php

use App\Http\Controllers\Configuracion\ApiLlavesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Configuración → API e integraciones
|--------------------------------------------------------------------------
| Llaves para otros sistemas y sesiones abiertas desde apps (/api/v1).
*/
Route::middleware(['auth', 'verified', 'approved', 'can:api.llaves'])
    ->prefix('configuracion/api')
    ->name('configuracion.api.')
    ->group(function () {
        Route::get('/', [ApiLlavesController::class, 'index'])->name('index');
        Route::post('/llaves', [ApiLlavesController::class, 'store'])->name('store');
        Route::delete('/tokens/{token}', [ApiLlavesController::class, 'destroy'])->name('destroy');
    });
