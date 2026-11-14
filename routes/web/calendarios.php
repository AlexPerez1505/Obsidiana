<?php

use App\Http\Controllers\Admin\AgendaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/calendarios/general', [AgendaController::class, 'general'])
        ->name('calendarios.general');

    Route::get('/calendarios/mio', [AgendaController::class, 'mio'])
        ->name('calendarios.mio');
});
