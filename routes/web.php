<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // La primera pantalla siempre es el login; si ya hay sesión, al panel.
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

/*
|--------------------------------------------------------------------------
| Invitados (no autenticados)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    // Recuperar contraseña (por código)
    Route::get('/recuperar-contrasena', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordResetController::class, 'sendCode'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/restablecer-contrasena', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Autenticados (con o sin correo verificado)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Verificación de correo por código
    Route::get('/verificar-correo', [VerifyEmailController::class, 'notice'])->name('verification.notice');
    Route::post('/verificar-correo', [VerifyEmailController::class, 'verify'])->name('verification.verify');
    Route::post('/verificar-correo/reenviar', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.resend');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Pantalla de "cuenta pendiente de aprobación" (verificado pero aún no aprobado)
    Route::get('/pendiente', [AccountController::class, 'pending'])
        ->middleware('verified')->name('approval.pending');
});

/*
|--------------------------------------------------------------------------
| Autenticados + correo verificado + cuenta APROBADA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/dashboard', [AccountController::class, 'show'])->name('dashboard');
    Route::get('/cuenta', [AccountController::class, 'show'])->name('account');
    Route::delete('/cuenta', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::post('/cuenta/cerrar-otras-sesiones', [AccountController::class, 'destroyOtherSessions'])
        ->name('account.sessions.destroyOthers');

    // Perfil
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/perfil/contrasena', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Panel de administración (solo admins)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
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
});
