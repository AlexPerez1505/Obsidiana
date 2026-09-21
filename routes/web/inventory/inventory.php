<?php

use App\Http\Controllers\EscaneoController;
use App\Http\Controllers\Inventory\FichaTecnicaController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\OrdenSalidaController;
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
        ->middleware('can:inventario.ver')->name('inventory.movimientos.index');
    Route::get('/gestion-inventario/entrada-salida/crear', [InventoryMovementController::class, 'create'])
        ->middleware('can:inventario.registrar')->name('inventory.movimientos.create');
    Route::post('/gestion-inventario/entrada-salida', [InventoryMovementController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('inventory.movimientos.store');
    Route::post('/gestion-inventario/entrada-salida/video-chunk', [InventoryMovementController::class, 'subirVideoChunk'])
        ->middleware('can:inventario.registrar')->name('inventory.movimientos.videoChunk');

    /*
    | Órdenes de salida: lo que almacén prepara, emplaya y firma cuando
    | se vende. Nacen solas al registrar la venta.
    */
    Route::middleware('can:salidas.ver')->prefix('/gestion-inventario/ordenes-salida')->name('inventory.salidas.')->group(function () {
        Route::get('/', [OrdenSalidaController::class, 'index'])->name('index');
        Route::get('/{orden}', [OrdenSalidaController::class, 'show'])->name('show');
        Route::get('/{orden}/pdf', [OrdenSalidaController::class, 'pdf'])->name('pdf');

        Route::middleware('can:salidas.preparar')->group(function () {
            Route::post('/{orden}/partidas/{item}', [OrdenSalidaController::class, 'item'])->name('item');
            Route::post('/{orden}/notas', [OrdenSalidaController::class, 'notas'])->name('notas');
            // Cierra la preparación: queda lista, esperando firma.
            Route::post('/{orden}/preparada', [OrdenSalidaController::class, 'preparada'])->name('preparada');
            Route::post('/{orden}/entregar', [OrdenSalidaController::class, 'entregar'])->name('entregar');
        });
    });
    /*
    | Procesos: las colas de hojalatería, mantenimiento y limpieza.
    */
    Route::get('/gestion-inventario/procesos', [ProcesoController::class, 'index'])
        ->middleware('can:procesos.ver')->name('inventory.procesos.index');
    Route::get('/gestion-inventario/procesos/{paso}', [ProcesoController::class, 'show'])
        ->middleware('can:procesos.ver')->name('inventory.procesos.show');
    Route::post('/gestion-inventario/procesos/{paso}/iniciar', [ProcesoController::class, 'iniciar'])
        ->middleware('can:procesos.trabajar')->name('inventory.procesos.iniciar');
    Route::post('/gestion-inventario/procesos/{paso}/terminar', [ProcesoController::class, 'terminar'])
        ->middleware('can:procesos.trabajar')->name('inventory.procesos.terminar');
    Route::post('/gestion-inventario/procesos/{paso}/omitir', [ProcesoController::class, 'omitir'])
        ->middleware('can:procesos.descartar')->name('inventory.procesos.omitir');
    Route::post('/gestion-inventario/procesos/pieza/{pieza}/agregar', [ProcesoController::class, 'agregar'])
        ->middleware('can:procesos.trabajar')->name('inventory.procesos.agregar');

    /*
    | Escaneo con pistola lectora: conteo, préstamos, revisión de un lote.
    */
    Route::get('/gestion-inventario/escanear', [EscaneoController::class, 'index'])
        ->middleware('can:inventario.escanear')->name('inventory.escaneo.index');
    Route::post('/gestion-inventario/escanear/buscar', [EscaneoController::class, 'buscar'])
        ->middleware('can:inventario.escanear')->name('inventory.escaneo.buscar');

    // Antes que /{movimiento}: si no, "etiquetas" se leería como un id.
    Route::get('/gestion-inventario/entrada-salida/{movimiento}/etiquetas', [InventoryMovementController::class, 'etiquetas'])
        ->middleware('can:inventario.ver')->name('inventory.movimientos.etiquetas');
    Route::get('/gestion-inventario/entrada-salida/{movimiento}', [InventoryMovementController::class, 'show'])
        ->middleware('can:inventario.ver')->name('inventory.movimientos.show');
    Route::delete('/gestion-inventario/entrada-salida/{movimiento}', [InventoryMovementController::class, 'destroy'])
        ->middleware('can:inventario.eliminar')->name('inventory.movimientos.destroy');

    // Productos (stock real, contra base de datos)
    Route::get('/gestion-inventario/productos', [ProductoController::class, 'index'])
        ->middleware('can:inventario.ver')->name('inventory.productos.index');
    Route::get('/gestion-inventario/productos/crear', [ProductoController::class, 'create'])
        ->middleware('can:inventario.registrar')->name('inventory.productos.create');
    Route::get('/gestion-inventario/productos/buscar-por-modelo', [ProductoController::class, 'buscarPorModelo'])
        ->middleware('can:inventario.ver')->name('inventory.productos.buscarPorModelo');
    // Series propias para equipo que no trae serial de fábrica.
    Route::post('/gestion-inventario/productos/generar-series', [ProductoController::class, 'generarSeries'])
        ->middleware('can:inventario.registrar')->name('inventory.productos.generarSeries');
    Route::post('/gestion-inventario/productos', [ProductoController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('inventory.productos.store');
    // La ficha del producto: qué entró, cuándo y cuánto hay.
    Route::get('/gestion-inventario/productos/{producto}', [ProductoController::class, 'show'])
        ->middleware('can:inventario.ver')->name('inventory.productos.show');
    Route::get('/gestion-inventario/productos/{producto}/editar', [ProductoController::class, 'edit'])
        ->middleware('can:inventario.editar')->name('inventory.productos.edit');
    Route::put('/gestion-inventario/productos/{producto}', [ProductoController::class, 'update'])
        ->middleware('can:inventario.editar')->name('inventory.productos.update');
    Route::delete('/gestion-inventario/productos/{producto}', [ProductoController::class, 'destroy'])
        ->middleware('can:inventario.eliminar')->name('inventory.productos.destroy');
    Route::post('/gestion-inventario/productos/{producto}/seriales', [ProductoController::class, 'agregarSeriales'])
        ->middleware('can:inventario.editar')->name('inventory.productos.seriales.store');
    Route::put('/gestion-inventario/productos/seriales/{serial}', [ProductoController::class, 'actualizarSerial'])
        ->middleware('can:inventario.editar')->name('inventory.productos.seriales.update');
    Route::delete('/gestion-inventario/productos/seriales/{serial}', [ProductoController::class, 'eliminarSerial'])
        ->middleware('can:inventario.eliminar')->name('inventory.productos.seriales.destroy');

    // Fichas técnicas (nombre + PDF)
    Route::get('/gestion-inventario/fichas-tecnicas', [FichaTecnicaController::class, 'index'])
        ->middleware('can:inventario.ver')->name('inventory.fichas.index');
    Route::get('/gestion-inventario/fichas-tecnicas/crear', [FichaTecnicaController::class, 'create'])
        ->middleware('can:inventario.registrar')->name('inventory.fichas.create');
    Route::post('/gestion-inventario/fichas-tecnicas', [FichaTecnicaController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('inventory.fichas.store');
    Route::get('/gestion-inventario/fichas-tecnicas/{ficha}/editar', [FichaTecnicaController::class, 'edit'])
        ->middleware('can:inventario.editar')->name('inventory.fichas.edit');
    Route::put('/gestion-inventario/fichas-tecnicas/{ficha}', [FichaTecnicaController::class, 'update'])
        ->middleware('can:inventario.editar')->name('inventory.fichas.update');
    Route::delete('/gestion-inventario/fichas-tecnicas/{ficha}', [FichaTecnicaController::class, 'destroy'])
        ->middleware('can:inventario.eliminar')->name('inventory.fichas.destroy');
    Route::get('/gestion-inventario/fichas-tecnicas/{ficha}/descargar', [FichaTecnicaController::class, 'download'])
        ->middleware('can:inventario.ver')->name('inventory.fichas.download');

    // Paquetes (armados desde productos)
    Route::get('/gestion-inventario/paquetes', [PaqueteController::class, 'index'])
        ->middleware('can:inventario.ver')->name('inventory.paquetes.index');
    Route::get('/gestion-inventario/paquetes/crear', [PaqueteController::class, 'create'])
        ->middleware('can:inventario.registrar')->name('inventory.paquetes.create');
    Route::post('/gestion-inventario/paquetes', [PaqueteController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('inventory.paquetes.store');
    Route::get('/gestion-inventario/paquetes/{paquete}/editar', [PaqueteController::class, 'edit'])
        ->middleware('can:inventario.editar')->name('inventory.paquetes.edit');
    Route::put('/gestion-inventario/paquetes/{paquete}', [PaqueteController::class, 'update'])
        ->middleware('can:inventario.editar')->name('inventory.paquetes.update');
    Route::delete('/gestion-inventario/paquetes/{paquete}', [PaqueteController::class, 'destroy'])
        ->middleware('can:inventario.eliminar')->name('inventory.paquetes.destroy');
});
