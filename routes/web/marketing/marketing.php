<?php

use App\Http\Controllers\Marketing\GuiaDeMarcaController;
use App\Http\Controllers\Marketing\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión de Marketing
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/marketing/tareas', [TaskController::class, 'index'])
        ->name('marketing.tareas.index');

    Route::get('/marketing/tareas/crear', [TaskController::class, 'create'])
        ->name('marketing.tareas.create');

    Route::post('/marketing/tareas', [TaskController::class, 'store'])
        ->name('marketing.tareas.store');

    Route::put('/marketing/tareas/{task}', [TaskController::class, 'update'])
        ->name('marketing.tareas.update');

    Route::delete('/marketing/tareas/{task}', [TaskController::class, 'destroy'])
        ->name('marketing.tareas.destroy');

    Route::put('/marketing/tareas/{task}/aprobar', [TaskController::class, 'aprobar'])
        ->name('marketing.tareas.aprobar');

    Route::put('/marketing/tareas/{task}/devolver', [TaskController::class, 'devolver'])
        ->name('marketing.tareas.devolver');

    Route::put('/marketing/tareas/{task}/enviar-revision', [TaskController::class, 'enviarRevision'])
        ->name('marketing.tareas.enviar_revision');

    Route::get('/marketing/aprobacion-flyers', [TaskController::class, 'aprobacionFlyers'])
        ->name('marketing.aprobacion_flyers.index');

    Route::get('/marketing/agenda', [TaskController::class, 'agenda'])
        ->name('marketing.agenda.index');

    Route::get('/marketing/calendario', function () {
        return view('structure.gestion_marketing.calendario.calendario');
    })->name('marketing.calendario.index');

    Route::get('/marketing/biblioteca-catalogo', [TaskController::class, 'bibliotecaCatalogo'])
        ->name('marketing.biblioteca_catalogo.index');

    Route::get('/marketing/biblioteca-catalogo/flyer/{task}/descargar', [TaskController::class, 'descargarFlyer'])
        ->name('marketing.biblioteca_catalogo.descargar_flyer');

    Route::get('/marketing/guia-de-marca', [GuiaDeMarcaController::class, 'index'])
        ->name('marketing.guia_de_marca.index');

    Route::get('/marketing/guia-de-marca/crear', [GuiaDeMarcaController::class, 'create'])
        ->name('marketing.guia_de_marca.create');

    Route::post('/marketing/guia-de-marca', [GuiaDeMarcaController::class, 'update'])
        ->name('marketing.guia_de_marca.update');

    Route::get('/gestion-marketing/inicio', [TaskController::class, 'inicio'])
        ->name('marketing.inicio');
});
