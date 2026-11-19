<?php

use App\Http\Controllers\Api\V1\Comercial\SolicitudWebController;
use Illuminate\Support\Facades\Route;

Route::middleware('can:tienda.ver')->prefix('solicitudes-web')->name('solicitudes-web.')->group(function () {
    Route::get('/', [SolicitudWebController::class, 'index'])->name('index');
    Route::get('/{solicitud}', [SolicitudWebController::class, 'show'])->whereNumber('solicitud')->name('show');
    Route::put('/{solicitud}', [SolicitudWebController::class, 'update'])->whereNumber('solicitud')
        ->middleware('can:tienda.atender')->name('update');
});
