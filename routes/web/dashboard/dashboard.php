<?php

use App\Http\Controllers\Dashboard\AccountController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticados + correo verificado + cuenta aprobada
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/metricas', [HomeController::class, 'metrics'])->name('dashboard.metrics');
    Route::get('/cuenta', [AccountController::class, 'show'])->name('account');
    Route::delete('/cuenta', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::post('/cuenta/cerrar-otras-sesiones', [AccountController::class, 'destroyOtherSessions'])
        ->name('account.sessions.destroyOthers');

    // Perfil
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/perfil/contrasena', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
