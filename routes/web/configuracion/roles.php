<?php

use App\Http\Controllers\Admin\RolController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Roles y permisos
|--------------------------------------------------------------------------
| Quién puede entrar aquí está protegido por el propio permiso que
| administra: solo alguien con "roles.gestionar" (o el administrador, que
| pasa antes de cualquier revisión) puede repartir permisos.
*/
Route::middleware(['auth', 'verified', 'approved', 'can:roles.gestionar'])->group(function () {
    Route::get('/configuracion/roles', [RolController::class, 'index'])
        ->name('configuracion.roles.index');

    Route::post('/configuracion/roles', [RolController::class, 'store'])
        ->name('configuracion.roles.store');

    Route::get('/configuracion/roles/{role}/permisos', [RolController::class, 'edit'])
        ->name('configuracion.roles.edit');

    Route::put('/configuracion/roles/{role}', [RolController::class, 'update'])
        ->name('configuracion.roles.update');

    Route::delete('/configuracion/roles/{role}', [RolController::class, 'destroy'])
        ->name('configuracion.roles.destroy');
});
