<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web
|--------------------------------------------------------------------------
| Este archivo solo carga los modulos de rutas que viven en routes/web/.
| No definas rutas aqui: agregalas al modulo que corresponda.
*/

Route::get('/', function () {
    // La primera pantalla siempre es el login; si ya hay sesion, al panel.
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

// Consulta publica del cliente (QR del PDF), sin sesion
require __DIR__.'/web/publico.php';

// Autenticacion y cuenta
require __DIR__.'/web/auth/auth.php';
require __DIR__.'/web/dashboard/dashboard.php';

// Gestion comercial
require __DIR__.'/web/commercial/commercial.php';
require __DIR__.'/web/cotizaciones.php';
require __DIR__.'/web/ventas.php';
require __DIR__.'/web/facturas.php';

// Inventario
require __DIR__.'/web/inventory.php';
require __DIR__.'/web/inventory/inventory.php';

// Configuracion
require __DIR__.'/web/configuracion/catalogo.php';
require __DIR__.'/web/configuracion/refaciones.php';
require __DIR__.'/web/configuracion/tipo_equipo.php';

// Marketing y servicios
require __DIR__.'/web/marketing/marketing.php';
require __DIR__.'/web/services/historial.php';

// Administracion
require __DIR__.'/web/admin/admin.php';
