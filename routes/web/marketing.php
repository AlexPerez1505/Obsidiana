<?php

use App\Http\Controllers\TaskController;
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

    Route::get('/marketing/aprobacion-flyers', [TaskController::class, 'aprobacionFlyers'])
        ->name('marketing.aprobacion_flyers.index');

    Route::get('/marketing/agenda', [TaskController::class, 'agenda'])
        ->name('marketing.agenda.index');

    Route::get('/marketing/biblioteca-catalogo', [TaskController::class, 'bibliotecaCatalogo'])
        ->name('marketing.biblioteca_catalogo.index');
});
