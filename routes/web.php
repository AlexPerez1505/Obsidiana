<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // La primera pantalla siempre es el login; si ya hay sesión, al panel.
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

require __DIR__.'/web/auth/auth.php';
require __DIR__.'/web/dashboard/dashboard.php';
require __DIR__.'/web/commercial/commercial.php';
require __DIR__.'/web/marketing/marketing.php';
require __DIR__.'/web/admin/admin.php';
require __DIR__.'/web/configuracion/catalogo.php';
require __DIR__.'/web/configuracion/tipo_equipo.php';
require __DIR__.'/web/configuracion/refaciones.php';
require __DIR__.'/web/inventory/inventory.php';
require __DIR__.'/web/services/historial.php';

Route::post('/verificar-pin', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'valid' => $request->user()?->checkApprovalPin($request->input('pin', '')) ?? false,
    ]);
})->middleware(['auth'])->name('verify.approval.pin');
