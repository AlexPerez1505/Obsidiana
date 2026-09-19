<?php

use App\Http\Controllers\Inventory\CongresoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Congresos (Gestión de Inventario)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved', 'can:congresos.ver'])->group(function () {
    Route::get('/gestion-inventario/congresos', [CongresoController::class, 'index'])
        ->name('inventory.congresos.index');
    Route::get('/gestion-inventario/congresos/crear', [CongresoController::class, 'create'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.create');
    Route::post('/gestion-inventario/congresos', [CongresoController::class, 'store'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.store');
    Route::get('/gestion-inventario/congresos/{congress}/editar', [CongresoController::class, 'edit'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.edit');
    Route::put('/gestion-inventario/congresos/{congress}', [CongresoController::class, 'update'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.update');
    Route::delete('/gestion-inventario/congresos/{congress}', [CongresoController::class, 'destroy'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.destroy');

    Route::get('/gestion-inventario/congresos/unidades-disponibles', [CongresoController::class, 'unidadesDisponibles'])
        ->name('inventory.congresos.unidadesDisponibles');

    Route::post('/gestion-inventario/congresos/{congress}/productos', [CongresoController::class, 'agregarProducto'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.productos.store');
    Route::delete('/gestion-inventario/congresos/{congress}/productos/{serial}', [CongresoController::class, 'quitarUnidad'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.productos.destroy');
    Route::post('/gestion-inventario/congresos/{congress}/regresar-todas', [CongresoController::class, 'regresarTodas'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.productos.regresarTodas');

    Route::post('/gestion-inventario/congresos/{congress}/participantes', [CongresoController::class, 'agregarParticipante'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.participantes.store');
    Route::delete('/gestion-inventario/congresos/{congress}/participantes/{participante}', [CongresoController::class, 'quitarParticipante'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.participantes.destroy');

    Route::post('/gestion-inventario/congresos/{congress}/usuarios', [CongresoController::class, 'agregarUsuario'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.usuarios.store');
    Route::delete('/gestion-inventario/congresos/{congress}/usuarios/{user}', [CongresoController::class, 'quitarUsuario'])
        ->middleware('can:congresos.editar')->name('inventory.congresos.usuarios.destroy');
});
