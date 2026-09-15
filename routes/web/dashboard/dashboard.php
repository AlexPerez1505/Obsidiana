<?php

use App\Http\Controllers\Dashboard\AccountController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticados + correo verificado + cuenta aprobada
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    // Tablero de inicio (antes /dashboard mostraba la pantalla de cuenta).
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/dashboard/tarjetas', [DashboardController::class, 'update'])->name('dashboard.widgets.update');
    Route::delete('/dashboard/tarjetas', [DashboardController::class, 'reset'])->name('dashboard.widgets.reset');

    Route::get('/cuenta', [AccountController::class, 'show'])->name('account');
    Route::delete('/cuenta', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::post('/cuenta/cerrar-otras-sesiones', [AccountController::class, 'destroyOtherSessions'])
        ->name('account.sessions.destroyOthers');

    // Campana de notificaciones
    Route::get('/notificaciones/{id}/abrir', [\App\Http\Controllers\NotificacionController::class, 'abrir'])->name('notificaciones.abrir');
    Route::post('/notificaciones/leer-todas', [\App\Http\Controllers\NotificacionController::class, 'leerTodas'])->name('notificaciones.leerTodas');

    // Perfil
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/perfil/contrasena', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Firma registrada: se traza una vez y de ahí la cargan las pantallas
    // que piden firma (entradas, salidas de almacén).
    Route::post('/perfil/firma', [ProfileController::class, 'updateFirma'])->name('profile.firma.store');
    Route::delete('/perfil/firma', [ProfileController::class, 'destroyFirma'])->name('profile.firma.destroy');
});
