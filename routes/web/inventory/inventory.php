<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión de Inventario
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-inventario/equipos', function () {
        return view('structure.gestion_Inventario.Equipos.menu_productos');
    })->name('inventory.equipos.index');

    Route::get('/gestion-inventario/equipos/crear', function () {
        return view('structure.gestion_Inventario.Equipos.c_productos');
    })->name('inventory.equipos.create');
});
