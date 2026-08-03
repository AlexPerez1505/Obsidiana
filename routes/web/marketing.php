<?php

use App\Http\Controllers\GuiaDeMarcaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión de Marketing
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-marketing/inicio', function () {
        return view('structure.gestion_marketing.inicio.menu_marketing');
    })->name('marketing.inicio');

    Route::get('/gestion-marketing/guia-de-marca', [GuiaDeMarcaController::class, 'index'])
        ->name('marketing.guia.index');
    Route::get('/gestion-marketing/guia-de-marca/crear', [GuiaDeMarcaController::class, 'create'])
        ->name('marketing.guia.create');
    Route::post('/gestion-marketing/guia-de-marca', [GuiaDeMarcaController::class, 'update'])
        ->name('marketing.guia.update');
});
