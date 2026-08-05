<?php

use App\Http\Controllers\Dashboard\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

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
