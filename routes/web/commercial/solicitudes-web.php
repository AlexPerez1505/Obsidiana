<?php

use App\Http\Controllers\Commercial\SolicitudesTiendaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión Comercial · Solicitudes web
|--------------------------------------------------------------------------
| Solicitudes de cotización que llegan desde la tienda en línea
| (POST /api/v1/tienda/solicitudes).
*/
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-comercial/solicitudes-web')
    ->name('commercial.solicitudes-web.')
    ->group(function () {
        Route::get('/', [SolicitudesTiendaController::class, 'index'])->middleware('can:tienda.ver')->name('index');
        Route::get('/{solicitud}', [SolicitudesTiendaController::class, 'show'])->middleware('can:tienda.ver')->name('show');
        Route::put('/{solicitud}', [SolicitudesTiendaController::class, 'update'])->middleware('can:tienda.atender')->name('update');
    });
