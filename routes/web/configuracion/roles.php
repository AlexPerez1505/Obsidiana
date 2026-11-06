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
    Route::get('/configuracion/usuarios/{user}/permisos', [\App\Http\Controllers\Admin\PermissionController::class, 'userPermissions'])
        ->name('configuracion.usuarios.permisos');
    Route::post('/configuracion/usuarios/{user}/permisos', [\App\Http\Controllers\Admin\PermissionController::class, 'updateUserPermissions'])
        ->name('configuracion.usuarios.permisos.update');

    Route::get('/configuracion/roles', [RolController::class, 'index'])
        ->name('configuracion.roles.index');

    Route::post('/configuracion/roles', [RolController::class, 'store'])
        ->name('configuracion.roles.store');

    Route::get('/configuracion/roles/{role}/permisos', [RolController::class, 'edit'])
        ->name('configuracion.roles.edit');

    Route::put('/configuracion/roles/{role}', [RolController::class, 'update'])
        ->name('configuracion.roles.update');

    Route::get('/configuracion/roles/{role}/usuarios/{user}', [RolController::class, 'editUsuario'])
        ->name('configuracion.roles.usuario.edit');

    Route::put('/configuracion/roles/{role}/usuarios/{user}', [RolController::class, 'updateUsuario'])
        ->name('configuracion.roles.usuario.update');

    Route::delete('/configuracion/roles/{role}/usuarios/{user}/ajustes', [RolController::class, 'restablecerUsuario'])
        ->name('configuracion.roles.usuario.reset');

    Route::delete('/configuracion/roles/{role}', [RolController::class, 'destroy'])
        ->middleware('can:roles.eliminar')->name('configuracion.roles.destroy');
});
