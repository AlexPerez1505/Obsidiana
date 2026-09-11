<?php

use App\Http\Controllers\Mantenimiento\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rol Mantenimiento
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/mantenimiento', [DashboardController::class, 'index'])->name('mantenimiento.dashboard');
});
