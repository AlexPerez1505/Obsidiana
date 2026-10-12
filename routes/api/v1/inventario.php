<?php

use App\Http\Controllers\Api\V1\Inventario\EscaneoController;
use App\Http\Controllers\Api\V1\Inventario\FichaTecnicaController;
use App\Http\Controllers\Api\V1\Inventario\MovimientoController;
use App\Http\Controllers\Api\V1\Inventario\PaqueteController;
use App\Http\Controllers\Api\V1\Inventario\ProductoController;
use App\Http\Controllers\Api\V1\Inventario\SerialController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — inventario. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Entradas y salidas, productos y sus piezas, fichas técnicas, paquetes y
| el escaneo con pistola. Cada ruta pide el mismo permiso que su pantalla
| en routes/web/inventory/inventory.php. Las pantallas de crear/editar se
| sustituyen por /catalogos (lo que el formulario necesita para llenarse).
|
| Fuera: órdenes de salida, procesos, congresos y catálogo de equipo
| (routes/api/v1/almacen.php).
*/

Route::prefix('movimientos')->name('movimientos.')->group(function () {
    Route::get('/', [MovimientoController::class, 'index'])
        ->middleware('can:inventario.ver')->name('index');
    Route::post('/', [MovimientoController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('store');
    Route::get('/catalogos', [MovimientoController::class, 'catalogos'])
        ->middleware('can:inventario.registrar')->name('catalogos');
    Route::post('/video-pedazo', [MovimientoController::class, 'subirVideo'])
        ->middleware('can:inventario.registrar')->name('video');

    Route::get('/{movimiento}', [MovimientoController::class, 'show'])
        ->whereNumber('movimiento')->middleware('can:inventario.ver')->name('show');
    Route::get('/{movimiento}/etiquetas', [MovimientoController::class, 'etiquetas'])
        ->whereNumber('movimiento')->middleware('can:inventario.ver')->name('etiquetas');
    Route::delete('/{movimiento}', [MovimientoController::class, 'destroy'])
        ->whereNumber('movimiento')->middleware('can:inventario.eliminar')->name('destroy');
});

Route::prefix('productos')->name('productos.')->group(function () {
    Route::get('/', [ProductoController::class, 'index'])
        ->middleware('can:inventario.ver')->name('index');
    Route::post('/', [ProductoController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('store');
    // Sirve al alta y a la edición: el controlador pide uno de los dos permisos.
    Route::get('/catalogos', [ProductoController::class, 'catalogos'])->name('catalogos');
    Route::get('/buscar-por-modelo', [ProductoController::class, 'buscarPorModelo'])
        ->middleware('can:inventario.ver')->name('buscar-por-modelo');
    Route::post('/generar-series', [ProductoController::class, 'generarSeries'])
        ->middleware('can:inventario.registrar')->name('generar-series');

    Route::get('/{producto}', [ProductoController::class, 'show'])
        ->whereNumber('producto')->middleware('can:inventario.ver')->name('show');
    Route::put('/{producto}', [ProductoController::class, 'update'])
        ->whereNumber('producto')->middleware('can:inventario.editar')->name('update');
    Route::delete('/{producto}', [ProductoController::class, 'destroy'])
        ->whereNumber('producto')->middleware('can:inventario.eliminar')->name('destroy');

    Route::post('/{producto}/seriales', [SerialController::class, 'store'])
        ->whereNumber('producto')->middleware('can:inventario.editar')->name('seriales.store');
    Route::put('/{producto}/seriales/{serial}', [SerialController::class, 'update'])
        ->whereNumber(['producto', 'serial'])->middleware('can:inventario.editar')->name('seriales.update');
    Route::delete('/{producto}/seriales/{serial}', [SerialController::class, 'destroy'])
        ->whereNumber(['producto', 'serial'])->middleware('can:inventario.eliminar')->name('seriales.destroy');
});

Route::prefix('fichas-tecnicas')->name('fichas-tecnicas.')->group(function () {
    Route::get('/', [FichaTecnicaController::class, 'index'])
        ->middleware('can:inventario.ver')->name('index');
    Route::post('/', [FichaTecnicaController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('store');
    Route::get('/{ficha}', [FichaTecnicaController::class, 'show'])
        ->whereNumber('ficha')->middleware('can:inventario.ver')->name('show');
    Route::put('/{ficha}', [FichaTecnicaController::class, 'update'])
        ->whereNumber('ficha')->middleware('can:inventario.editar')->name('update');
    Route::delete('/{ficha}', [FichaTecnicaController::class, 'destroy'])
        ->whereNumber('ficha')->middleware('can:inventario.eliminar')->name('destroy');
    Route::get('/{ficha}/descargar', [FichaTecnicaController::class, 'descargar'])
        ->whereNumber('ficha')->middleware('can:inventario.ver')->name('descargar');
});

Route::prefix('paquetes')->name('paquetes.')->group(function () {
    Route::get('/', [PaqueteController::class, 'index'])
        ->middleware('can:inventario.ver')->name('index');
    Route::post('/', [PaqueteController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('store');
    Route::get('/{paquete}', [PaqueteController::class, 'show'])
        ->whereNumber('paquete')->middleware('can:inventario.ver')->name('show');
    Route::put('/{paquete}', [PaqueteController::class, 'update'])
        ->whereNumber('paquete')->middleware('can:inventario.editar')->name('update');
    Route::delete('/{paquete}', [PaqueteController::class, 'destroy'])
        ->whereNumber('paquete')->middleware('can:inventario.eliminar')->name('destroy');
});

// Pistola lectora: devuelve la pieza de la etiqueta que se escaneó.
Route::post('/escanear', [EscaneoController::class, 'buscar'])
    ->middleware('can:inventario.escanear')->name('escaneo.buscar');
