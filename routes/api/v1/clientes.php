<?php

use App\Http\Controllers\Api\V1\Comercial\BuscadorController;
use App\Http\Controllers\Api\V1\Comercial\CategoriaClienteController;
use App\Http\Controllers\Api\V1\Comercial\ClienteController;
use App\Http\Controllers\Api\V1\Comercial\CotizacionController;
use App\Http\Controllers\Api\V1\Comercial\SeguimientoController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — clientes. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Mismos permisos que routes/web/commercial/commercial.php y
| routes/web/cotizaciones.php. Las remisiones y el registro de pagos de
| planes viejos no se exponen: en la web sus rutas siguen declaradas pero
| apuntan a métodos que ya no existen (las reemplazó Ventas).
*/

// Clientes. Las rutas fijas van antes de {cliente} para que "similar" o
// "catalogos" no se tomen como un id.
Route::prefix('clientes')->name('clientes.')->whereNumber(['cliente', 'seguimiento'])->group(function () {
    Route::get('/', [ClienteController::class, 'index'])
        ->middleware('can:clientes.ver')->name('index');
    Route::get('/catalogos', [ClienteController::class, 'catalogos'])
        ->middleware('can:clientes.ver')->name('catalogos');
    Route::get('/similar', [ClienteController::class, 'similar'])
        ->middleware('can:clientes.ver')->name('similar');
    Route::post('/', [ClienteController::class, 'store'])
        ->middleware('can:clientes.crear')->name('store');
    Route::post('/categorias', [CategoriaClienteController::class, 'store'])
        ->middleware('can:clientes.crear')->name('categorias.store');

    Route::get('/{cliente}', [ClienteController::class, 'show'])
        ->middleware('can:clientes.ver')->name('show');
    Route::put('/{cliente}', [ClienteController::class, 'update'])
        ->middleware('can:clientes.editar')->name('update');
    Route::post('/{cliente}/convertir', [ClienteController::class, 'convertir'])
        ->middleware('can:clientes.editar')->name('convertir');

    // Seguimientos (recordatorios) del cliente o prospecto.
    Route::post('/{cliente}/seguimientos', [SeguimientoController::class, 'store'])
        ->middleware('can:clientes.editar')->name('seguimientos.store');
    Route::post('/{cliente}/seguimientos/{seguimiento}/hecho', [SeguimientoController::class, 'hecho'])
        ->middleware('can:clientes.editar')->name('seguimientos.hecho');
    Route::post('/{cliente}/seguimientos/{seguimiento}/reprogramar', [SeguimientoController::class, 'reprogramar'])
        ->middleware('can:clientes.editar')->name('seguimientos.reprogramar');
    Route::delete('/{cliente}/seguimientos/{seguimiento}', [SeguimientoController::class, 'destroy'])
        ->middleware('can:clientes.editar')->name('seguimientos.destroy');
});

// Cotizaciones. {cotizacion} pasa por el binding global que da 403 si es
// de otro asesor y no se tiene cotizaciones.ver_todas.
Route::prefix('cotizaciones')->name('cotizaciones.')->whereNumber('cotizacion')->group(function () {
    // Buscadores del formulario (también los usa el de ventas). El permiso
    // lo revisa el controlador: basta con poder crear o editar cotizaciones o ventas.
    Route::get('/buscar/clientes', [BuscadorController::class, 'clientes'])->name('buscar.clientes');
    Route::get('/buscar/productos', [BuscadorController::class, 'productos'])->name('buscar.productos');
    Route::get('/buscar/fichas', [BuscadorController::class, 'fichas'])->name('buscar.fichas');

    Route::get('/catalogos', [CotizacionController::class, 'catalogos'])
        ->middleware('can:cotizaciones.ver')->name('catalogos');
    // Pide crear o editar; lo revisa el controlador.
    Route::post('/calcular', [CotizacionController::class, 'calcular'])->name('calcular');

    Route::get('/', [CotizacionController::class, 'index'])
        ->middleware('can:cotizaciones.ver')->name('index');
    Route::post('/', [CotizacionController::class, 'store'])
        ->middleware('can:cotizaciones.crear')->name('store');
    Route::get('/{cotizacion}', [CotizacionController::class, 'show'])
        ->middleware('can:cotizaciones.ver')->name('show');
    Route::put('/{cotizacion}', [CotizacionController::class, 'update'])
        ->middleware('can:cotizaciones.editar')->name('update');
    Route::delete('/{cotizacion}', [CotizacionController::class, 'destroy'])
        ->middleware('can:cotizaciones.eliminar')->name('destroy');
    Route::get('/{cotizacion}/pdf', [CotizacionController::class, 'pdf'])
        ->middleware('can:cotizaciones.ver')->name('pdf');
});
