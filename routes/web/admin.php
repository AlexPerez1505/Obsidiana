<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\ViaticController;
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

    // Vehículos
    Route::get('/vehiculos', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::post('/vehiculos', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehiculos/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');

    // Viáticos
    Route::get('/viaticos', [ViaticController::class, 'index'])->name('viatics.index');
    Route::get('/viaticos/crear', [ViaticController::class, 'create'])->name('viatics.create');
    Route::post('/viaticos', [ViaticController::class, 'store'])->name('viatics.store');
    Route::get('/viaticos/{viatic}/editar', [ViaticController::class, 'edit'])->name('viatics.edit');
    Route::patch('/viaticos/{viatic}', [ViaticController::class, 'update'])->name('viatics.update');
    Route::delete('/viaticos/{viatic}', [ViaticController::class, 'destroy'])->name('viatics.destroy');
    Route::get('/viaticos/{viatic}', [ViaticController::class, 'show'])->name('viatics.show');
    Route::post('/viaticos/{viatic}/gastos', [ViaticController::class, 'addExpense'])->name('viatics.expense');
    Route::patch('/viaticos/{viatic}/gastos/{expense}', [ViaticController::class, 'updateExpense'])->name('viatics.expense.update');
    Route::delete('/viaticos/{viatic}/gastos/{expense}', [ViaticController::class, 'destroyExpense'])->name('viatics.expense.destroy');

    // Viajes en curso
    Route::post('/viajes', [TripController::class, 'store'])->name('trips.store');
    Route::get('/viajes/{trip}', [TripController::class, 'show'])->name('trips.show');
    Route::post('/viajes/{trip}/gastos', [TripController::class, 'addExpense'])->name('trips.expense');
    Route::patch('/viajes/{trip}/gastos/{expense}', [TripController::class, 'updateExpense'])->name('trips.expense.update');
    Route::delete('/viajes/{trip}/gastos/{expense}', [TripController::class, 'destroyExpense'])->name('trips.expense.destroy');
    Route::post('/viajes/{trip}/finalizar', [TripController::class, 'finish'])->name('trips.finish');

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
