<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // La primera pantalla siempre es el login; si ya hay sesión, al panel.
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

require __DIR__.'/web/auth.php';
require __DIR__.'/web/dashboard.php';
require __DIR__.'/web/commercial.php';
require __DIR__.'/web/cotizaciones.php';
require __DIR__.'/web/ventas.php';
require __DIR__.'/web/facturas.php';
require __DIR__.'/web/admin.php';
require __DIR__.'/web/catalogo.php';
require __DIR__.'/web/marketing.php';
require __DIR__.'/web/inventory.php';
