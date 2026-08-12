<?php

use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión de Inventario
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-inventario/entrada-salida', function () {
        return view('structure.gestion_Inventario.entrada_salida.index');
    })->name('inventory.movimientos.index');

    Route::get('/gestion-inventario/entrada-salida/crear', function () {
        return view('structure.gestion_Inventario.entrada_salida.create');
    })->name('inventory.movimientos.create');

    Route::get('/gestion-inventario/equipos', [EquipmentController::class, 'index'])
        ->name('inventory.equipos.index');
    Route::get('/gestion-inventario/equipos/crear', [EquipmentController::class, 'create'])
        ->name('inventory.equipos.create');
    Route::post('/gestion-inventario/equipos', [EquipmentController::class, 'store'])
        ->name('inventory.equipos.store');
    Route::get('/gestion-inventario/equipos/{equipo}/editar', [EquipmentController::class, 'edit'])
        ->name('inventory.equipos.edit');
    Route::put('/gestion-inventario/equipos/{equipo}', [EquipmentController::class, 'update'])
        ->name('inventory.equipos.update');
    Route::delete('/gestion-inventario/equipos/{equipo}', [EquipmentController::class, 'destroy'])
        ->name('inventory.equipos.destroy');
    Route::get('/gestion-inventario/equipos/{equipo}/detalle', [EquipmentController::class, 'show'])
        ->name('inventory.equipos.show');

    // Productos (stock real, contra base de datos)
    Route::get('/gestion-inventario/productos', [ProductoController::class, 'index'])
        ->name('inventory.productos.index');
    Route::get('/gestion-inventario/productos/crear', [ProductoController::class, 'create'])
        ->name('inventory.productos.create');
    Route::post('/gestion-inventario/productos', [ProductoController::class, 'store'])
        ->name('inventory.productos.store');
    Route::post('/gestion-inventario/productos/sincronizar', [ProductoController::class, 'sync'])
        ->name('inventory.productos.sync');
    Route::get('/gestion-inventario/productos/{producto}/editar', [ProductoController::class, 'edit'])
        ->name('inventory.productos.edit');
    Route::put('/gestion-inventario/productos/{producto}', [ProductoController::class, 'update'])
        ->name('inventory.productos.update');
    Route::delete('/gestion-inventario/productos/{producto}', [ProductoController::class, 'destroy'])
        ->name('inventory.productos.destroy');

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
