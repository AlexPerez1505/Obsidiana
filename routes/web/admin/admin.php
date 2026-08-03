<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel de administración (solo admins)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
    Route::get('/usuarios/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/usuarios/{user}/admin', [UserController::class, 'toggleAdmin'])->name('users.toggleAdmin');
    Route::post('/usuarios/{user}/aprobar', [UserController::class, 'approve'])->name('users.approve');
    Route::post('/usuarios/{user}/banear', [UserController::class, 'ban'])->name('users.ban');
    Route::post('/usuarios/{user}/desbanear', [UserController::class, 'unban'])->name('users.unban');

    // Permisos
    Route::get('/permisos', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permisos/crear', [PermissionController::class, 'create'])->name('permissions.create');
    Route::post('/permisos', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('/permisos/{permission}/editar', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::patch('/permisos/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('/permisos/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    Route::get('/usuarios/{user}/permisos', [PermissionController::class, 'userPermissions'])->name('users.permissions');
    Route::post('/usuarios/{user}/permisos', [PermissionController::class, 'updateUserPermissions'])->name('users.permissions.update');
});
