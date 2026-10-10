<?php

use App\Http\Controllers\Api\V1\Almacen\CategoriaController;
use App\Http\Controllers\Api\V1\Almacen\CongresoController;
use App\Http\Controllers\Api\V1\Almacen\EquipoController;
use App\Http\Controllers\Api\V1\Almacen\MarcaController;
use App\Http\Controllers\Api\V1\Almacen\ModeloController;
use App\Http\Controllers\Api\V1\Almacen\OrdenSalidaController;
use App\Http\Controllers\Api\V1\Almacen\ProcesoController;
use App\Http\Controllers\Api\V1\Almacen\RefaccionController;
use App\Http\Controllers\Api\V1\Almacen\SubtipoController;
use App\Http\Controllers\Api\V1\Almacen\TipoEquipoController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — almacen. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Cada ruta pide el mismo permiso que su pantalla en la web
| (routes/web/inventory/inventory.php, congresos.php y
| routes/web/configuracion/catalogo.php, tipo_equipo.php, refaciones.php).
*/

/*
| Órdenes de salida: nacen solas al vender; almacén las prepara, emplaya y
| firma.
*/
Route::middleware('can:salidas.ver')->prefix('ordenes-salida')->name('ordenes-salida.')
    ->whereNumber(['orden', 'item'])
    ->group(function () {
        Route::get('/', [OrdenSalidaController::class, 'index'])->name('index');
        Route::get('/{orden}', [OrdenSalidaController::class, 'show'])->name('show');
        Route::get('/{orden}/pdf', [OrdenSalidaController::class, 'pdf'])->name('pdf');

        Route::middleware('can:salidas.preparar')->group(function () {
            Route::patch('/{orden}/partidas/{item}', [OrdenSalidaController::class, 'partida'])->name('partida');
            Route::put('/{orden}/notas', [OrdenSalidaController::class, 'notas'])->name('notas');
            Route::post('/{orden}/confirmar-preparacion', [OrdenSalidaController::class, 'confirmarPreparacion'])->name('confirmar-preparacion');
            Route::post('/{orden}/entregar', [OrdenSalidaController::class, 'entregar'])->name('entregar');
        });
    });

/*
| Procesos: colas de hojalatería, mantenimiento y limpieza.
*/
Route::prefix('procesos')->name('procesos.')->whereNumber(['paso', 'pieza'])->group(function () {
    Route::get('/', [ProcesoController::class, 'index'])
        ->middleware('can:procesos.ver')->name('index');
    Route::get('/{paso}', [ProcesoController::class, 'show'])
        ->middleware('can:procesos.ver')->name('show');
    Route::post('/{paso}/iniciar', [ProcesoController::class, 'iniciar'])
        ->middleware('can:procesos.trabajar')->name('iniciar');
    Route::post('/{paso}/terminar', [ProcesoController::class, 'terminar'])
        ->middleware('can:procesos.trabajar')->name('terminar');
    Route::post('/{paso}/omitir', [ProcesoController::class, 'omitir'])
        ->middleware('can:procesos.descartar')->name('omitir');
    Route::post('/piezas/{pieza}/agregar', [ProcesoController::class, 'agregar'])
        ->middleware('can:procesos.trabajar')->name('agregar');
    // Corregir la ruta completa de una pieza: mismo permiso que registrar la entrada.
    Route::put('/piezas/{pieza}/definir', [ProcesoController::class, 'definir'])
        ->middleware('can:inventario.registrar')->name('definir');
});

/*
| Congresos.
*/
Route::middleware('can:congresos.ver')->prefix('congresos')->name('congresos.')
    ->whereNumber(['congreso', 'serial', 'participante', 'user'])
    ->group(function () {
        Route::get('/', [CongresoController::class, 'index'])->name('index');
        Route::get('/unidades-disponibles', [CongresoController::class, 'unidadesDisponibles'])->name('unidades-disponibles');
        Route::get('/{congreso}', [CongresoController::class, 'show'])->name('show');

        Route::middleware('can:congresos.editar')->group(function () {
            Route::get('/catalogos', [CongresoController::class, 'catalogos'])->name('catalogos');
            Route::post('/', [CongresoController::class, 'store'])->name('store');
            Route::put('/{congreso}', [CongresoController::class, 'update'])->name('update');
            Route::delete('/{congreso}', [CongresoController::class, 'destroy'])->name('destroy');

            Route::post('/{congreso}/unidades', [CongresoController::class, 'agregarUnidades'])->name('unidades.store');
            Route::delete('/{congreso}/unidades/{serial}', [CongresoController::class, 'quitarUnidad'])->name('unidades.destroy');
            Route::post('/{congreso}/regresar-todas', [CongresoController::class, 'regresarTodas'])->name('unidades.regresar-todas');

            Route::post('/{congreso}/participantes', [CongresoController::class, 'agregarParticipante'])->name('participantes.store');
            Route::delete('/{congreso}/participantes/{participante}', [CongresoController::class, 'quitarParticipante'])->name('participantes.destroy');

            Route::post('/{congreso}/usuarios', [CongresoController::class, 'agregarUsuario'])->name('usuarios.store');
            Route::delete('/{congreso}/usuarios/{user}', [CongresoController::class, 'quitarUsuario'])->name('usuarios.destroy');
        });
    });

/*
| Catálogo: categorías y tipo → subtipo → marca → modelo.
*/
Route::prefix('catalogo')->name('catalogo.')->group(function () {
    Route::middleware('can:inventario.catalogo')->group(function () {
        Route::apiResource('categorias', CategoriaController::class)
            ->parameters(['categorias' => 'categoria'])->whereNumber('categoria');
        Route::apiResource('tipos', TipoEquipoController::class)
            ->parameters(['tipos' => 'tipo'])->whereNumber('tipo');
        Route::apiResource('subtipos', SubtipoController::class)
            ->parameters(['subtipos' => 'subtipo'])->whereNumber('subtipo');
        Route::apiResource('marcas', MarcaController::class)
            ->parameters(['marcas' => 'marca'])->whereNumber('marca');
        Route::apiResource('modelos', ModeloController::class)
            ->parameters(['modelos' => 'modelo'])->whereNumber('modelo');

        Route::get('/equipos', [EquipoController::class, 'index'])->name('equipos.index');
        Route::post('/equipos', [EquipoController::class, 'store'])->name('equipos.store');
    });

    // Igual que en la web, la cascada por nombre del alta rápida no pide permiso.
    Route::get('/equipos/subtipos', [EquipoController::class, 'subtipos'])->name('equipos.subtipos');
    Route::get('/equipos/modelos', [EquipoController::class, 'modelos'])->name('equipos.modelos');
});

/*
| Refacciones.
*/
Route::prefix('refacciones')->name('refacciones.')->whereNumber('refaccion')->group(function () {
    Route::get('/', [RefaccionController::class, 'index'])
        ->middleware('can:inventario.ver')->name('index');
    Route::get('/catalogos', [RefaccionController::class, 'catalogos'])
        ->middleware('can:inventario.registrar')->name('catalogos');
    Route::get('/{refaccion}', [RefaccionController::class, 'show'])
        ->middleware('can:inventario.ver')->name('show');
    Route::post('/', [RefaccionController::class, 'store'])
        ->middleware('can:inventario.registrar')->name('store');
    Route::delete('/{refaccion}', [RefaccionController::class, 'destroy'])
        ->middleware('can:inventario.eliminar')->name('destroy');
});
