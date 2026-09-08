<?php

use App\Http\Controllers\EscaneoController;
use App\Http\Controllers\Inventory\FichaTecnicaController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\ProcesoController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión de Inventario
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-inventario/entrada-salida', [InventoryMovementController::class, 'index'])
        ->name('inventory.movimientos.index');
    Route::get('/gestion-inventario/entrada-salida/crear', [InventoryMovementController::class, 'create'])
        ->name('inventory.movimientos.create');
    Route::post('/gestion-inventario/entrada-salida', [InventoryMovementController::class, 'store'])
        ->name('inventory.movimientos.store');
    Route::post('/gestion-inventario/entrada-salida/video-chunk', [InventoryMovementController::class, 'subirVideoChunk'])
        ->name('inventory.movimientos.videoChunk');
    /*
    | Procesos: las colas de hojalatería, mantenimiento y limpieza.
    */
    Route::get('/gestion-inventario/procesos', [ProcesoController::class, 'index'])
        ->name('inventory.procesos.index');
    Route::get('/gestion-inventario/procesos/{paso}', [ProcesoController::class, 'show'])
        ->name('inventory.procesos.show');
    Route::post('/gestion-inventario/procesos/{paso}/iniciar', [ProcesoController::class, 'iniciar'])
        ->name('inventory.procesos.iniciar');
    Route::post('/gestion-inventario/procesos/{paso}/terminar', [ProcesoController::class, 'terminar'])
        ->name('inventory.procesos.terminar');
    Route::post('/gestion-inventario/procesos/{paso}/omitir', [ProcesoController::class, 'omitir'])
        ->name('inventory.procesos.omitir');
    Route::post('/gestion-inventario/procesos/pieza/{pieza}/agregar', [ProcesoController::class, 'agregar'])
        ->name('inventory.procesos.agregar');

    /*
    | Escaneo con pistola lectora: conteo, préstamos, revisión de un lote.
    */
    Route::get('/gestion-inventario/escanear', [EscaneoController::class, 'index'])
        ->name('inventory.escaneo.index');
    Route::post('/gestion-inventario/escanear/buscar', [EscaneoController::class, 'buscar'])
        ->name('inventory.escaneo.buscar');

    // Antes que /{movimiento}: si no, "etiquetas" se leería como un id.
    Route::get('/gestion-inventario/entrada-salida/{movimiento}/etiquetas', [InventoryMovementController::class, 'etiquetas'])
        ->name('inventory.movimientos.etiquetas');
    Route::get('/gestion-inventario/entrada-salida/{movimiento}', [InventoryMovementController::class, 'show'])
        ->name('inventory.movimientos.show');
    Route::delete('/gestion-inventario/entrada-salida/{movimiento}', [InventoryMovementController::class, 'destroy'])
        ->name('inventory.movimientos.destroy');

    // Productos (stock real, contra base de datos)
    Route::get('/gestion-inventario/productos', [ProductoController::class, 'index'])
        ->name('inventory.productos.index');
    Route::get('/gestion-inventario/productos/crear', [ProductoController::class, 'create'])
        ->name('inventory.productos.create');
    Route::get('/gestion-inventario/productos/buscar-por-modelo', [ProductoController::class, 'buscarPorModelo'])
        ->name('inventory.productos.buscarPorModelo');
    // Series propias para equipo que no trae serial de fábrica.
    Route::post('/gestion-inventario/productos/generar-series', [ProductoController::class, 'generarSeries'])
        ->name('inventory.productos.generarSeries');
    Route::post('/gestion-inventario/productos', [ProductoController::class, 'store'])
        ->name('inventory.productos.store');
    // La ficha del producto: qué entró, cuándo y cuánto hay.
    Route::get('/gestion-inventario/productos/{producto}', [ProductoController::class, 'show'])
        ->name('inventory.productos.show');
    Route::get('/gestion-inventario/productos/{producto}/editar', [ProductoController::class, 'edit'])
        ->name('inventory.productos.edit');
    Route::put('/gestion-inventario/productos/{producto}', [ProductoController::class, 'update'])
        ->name('inventory.productos.update');
    Route::delete('/gestion-inventario/productos/{producto}', [ProductoController::class, 'destroy'])
        ->name('inventory.productos.destroy');
    Route::post('/gestion-inventario/productos/{producto}/seriales', [ProductoController::class, 'agregarSeriales'])
        ->name('inventory.productos.seriales.store');
    Route::put('/gestion-inventario/productos/seriales/{serial}', [ProductoController::class, 'actualizarSerial'])
        ->name('inventory.productos.seriales.update');
    Route::delete('/gestion-inventario/productos/seriales/{serial}', [ProductoController::class, 'eliminarSerial'])
        ->name('inventory.productos.seriales.destroy');

    // Fichas técnicas (nombre + PDF)
    Route::get('/gestion-inventario/fichas-tecnicas', [FichaTecnicaController::class, 'index'])
        ->name('inventory.fichas.index');
    Route::get('/gestion-inventario/fichas-tecnicas/crear', [FichaTecnicaController::class, 'create'])
        ->name('inventory.fichas.create');
    Route::post('/gestion-inventario/fichas-tecnicas', [FichaTecnicaController::class, 'store'])
        ->name('inventory.fichas.store');
    Route::get('/gestion-inventario/fichas-tecnicas/{ficha}/editar', [FichaTecnicaController::class, 'edit'])
        ->name('inventory.fichas.edit');
    Route::put('/gestion-inventario/fichas-tecnicas/{ficha}', [FichaTecnicaController::class, 'update'])
        ->name('inventory.fichas.update');
    Route::delete('/gestion-inventario/fichas-tecnicas/{ficha}', [FichaTecnicaController::class, 'destroy'])
        ->name('inventory.fichas.destroy');
    Route::get('/gestion-inventario/fichas-tecnicas/{ficha}/descargar', [FichaTecnicaController::class, 'download'])
        ->name('inventory.fichas.download');

    // Paquetes (armados desde productos)
    Route::get('/gestion-inventario/paquetes', [PaqueteController::class, 'index'])
        ->name('inventory.paquetes.index');
    Route::get('/gestion-inventario/paquetes/crear', [PaqueteController::class, 'create'])
        ->name('inventory.paquetes.create');
    Route::post('/gestion-inventario/paquetes', [PaqueteController::class, 'store'])
        ->name('inventory.paquetes.store');
    Route::get('/gestion-inventario/paquetes/{paquete}/editar', [PaqueteController::class, 'edit'])
        ->name('inventory.paquetes.edit');
    Route::put('/gestion-inventario/paquetes/{paquete}', [PaqueteController::class, 'update'])
        ->name('inventory.paquetes.update');
    Route::delete('/gestion-inventario/paquetes/{paquete}', [PaqueteController::class, 'destroy'])
        ->name('inventory.paquetes.destroy');
});
