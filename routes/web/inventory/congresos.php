<?php

use App\Http\Controllers\Inventory\CongresoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Congresos (Gestión de Inventario)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-inventario/congresos', [CongresoController::class, 'index'])
        ->name('inventory.congresos.index');
    Route::get('/gestion-inventario/congresos/crear', [CongresoController::class, 'create'])
        ->name('inventory.congresos.create');
    Route::post('/gestion-inventario/congresos', [CongresoController::class, 'store'])
        ->name('inventory.congresos.store');
    Route::get('/gestion-inventario/congresos/{congress}/editar', [CongresoController::class, 'edit'])
        ->name('inventory.congresos.edit');
    Route::put('/gestion-inventario/congresos/{congress}', [CongresoController::class, 'update'])
        ->name('inventory.congresos.update');
    Route::delete('/gestion-inventario/congresos/{congress}', [CongresoController::class, 'destroy'])
        ->name('inventory.congresos.destroy');

    Route::get('/gestion-inventario/congresos/unidades-disponibles', [CongresoController::class, 'unidadesDisponibles'])
        ->name('inventory.congresos.unidadesDisponibles');

    Route::post('/gestion-inventario/congresos/{congress}/productos', [CongresoController::class, 'agregarProducto'])
        ->name('inventory.congresos.productos.store');
    Route::delete('/gestion-inventario/congresos/{congress}/productos/{serial}', [CongresoController::class, 'quitarUnidad'])
        ->name('inventory.congresos.productos.destroy');
    Route::post('/gestion-inventario/congresos/{congress}/regresar-todas', [CongresoController::class, 'regresarTodas'])
        ->name('inventory.congresos.productos.regresarTodas');

    Route::post('/gestion-inventario/congresos/{congress}/participantes', [CongresoController::class, 'agregarParticipante'])
        ->name('inventory.congresos.participantes.store');
    Route::delete('/gestion-inventario/congresos/{congress}/participantes/{participante}', [CongresoController::class, 'quitarParticipante'])
        ->name('inventory.congresos.participantes.destroy');

    Route::post('/gestion-inventario/congresos/{congress}/usuarios', [CongresoController::class, 'agregarUsuario'])
        ->name('inventory.congresos.usuarios.store');
    Route::delete('/gestion-inventario/congresos/{congress}/usuarios/{user}', [CongresoController::class, 'quitarUsuario'])
        ->name('inventory.congresos.usuarios.destroy');
});
