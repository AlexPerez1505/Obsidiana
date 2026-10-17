<?php

use App\Http\Controllers\Api\V1\Tienda\TiendaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tienda en línea (pública, sin token)
|--------------------------------------------------------------------------
| La consume el sitio en React. Solo muestra el catálogo sin precios y
| recibe solicitudes de cotización. Con límite por IP para no usarse como
| buzón de spam (limitadores en AppServiceProvider).
*/
Route::prefix('tienda')->name('tienda.')->group(function () {
    Route::middleware('throttle:tienda')->group(function () {
        Route::get('/config', [TiendaController::class, 'config'])->name('config');
        Route::get('/filtros', [TiendaController::class, 'filtros'])->name('filtros');
        Route::get('/catalogo', [TiendaController::class, 'catalogo'])->name('catalogo');
        Route::get('/catalogo/{clave}', [TiendaController::class, 'articulo'])
            ->where('clave', '(producto|equipo|paquete)-[0-9]+')->name('articulo');
    });

    Route::post('/solicitudes', [TiendaController::class, 'solicitar'])
        ->middleware('throttle:tienda-solicitudes')->name('solicitudes');
});
