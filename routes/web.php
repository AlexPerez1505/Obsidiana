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
        ? redirect()->route(auth()->user()->homeRoute())
        : redirect()->route('login');
})->name('home');

// Corrector ortográfico (español de México) para todo lo que se escribe.
Route::post('/_ortografia', function (\Illuminate\Http\Request $request, \App\Services\Ortografia\CorrectorOrtografico $corrector) {
    $data = $request->validate(['texto' => ['required', 'string', 'max:'.\App\Services\Ortografia\CorrectorOrtografico::MAXIMO]]);

    return response()->json($corrector->corregir($data['texto']));
})->middleware(['auth', 'throttle:120,1'])->name('ortografia.corregir');

// Consulta publica del cliente (QR del PDF), sin sesion
// Tiempo real: las pantallas preguntan cada pocos segundos si algo cambió.
// Es solo un número, así que va sin sesión ni cookies (no toca la base).
Route::get('/_vivo', fn () => response()->json(['rev' => \App\Support\ObsVivo::rev()])
    ->header('Cache-Control', 'no-store'))
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \App\Http\Middleware\RestrictMantenimientoAccess::class,
        \App\Http\Middleware\ObsVivoTocar::class,
    ])
    ->name('vivo');

require __DIR__.'/web/publico.php';

// Autenticacion y cuenta
require __DIR__.'/web/auth/auth.php';
require __DIR__.'/web/dashboard/dashboard.php';
require __DIR__.'/web/mantenimiento/mantenimiento.php';

// Gestion comercial
require __DIR__.'/web/commercial/commercial.php';
require __DIR__.'/web/cotizaciones.php';
require __DIR__.'/web/ventas.php';
require __DIR__.'/web/facturas.php';
require __DIR__.'/web/commercial/solicitudes-web.php';

// Marketing y promociones (campañas por WhatsApp, cupones, oferta pública y webhook)
require __DIR__.'/web/promociones.php';

// Inventario
require __DIR__.'/web/inventory/inventory.php';
require __DIR__.'/web/inventory/congresos.php';

// Logistica y rutas
require __DIR__.'/web/logistica.php';

// Calendarios principales del sistema
require __DIR__.'/web/calendarios.php';

// Recursos humanos: asistencia con lector de huella
require __DIR__.'/web/asistencia.php';
require __DIR__.'/web/nomina.php';

// Configuracion
require __DIR__.'/web/configuracion/catalogo.php';
require __DIR__.'/web/configuracion/roles.php';
require __DIR__.'/web/configuracion/refaciones.php';
require __DIR__.'/web/configuracion/tipo_equipo.php';
require __DIR__.'/web/configuracion/api.php';

// Marketing
require __DIR__.'/web/marketing/marketing.php';

// Gestion de servicios
require __DIR__.'/web/services/historial.php';
require __DIR__.'/web/services/registro.php';
require __DIR__.'/web/services/servicio_externo.php';
require __DIR__.'/web/services/endoscopia.php';
require __DIR__.'/web/services/refacciones.php';

// Garantías
require __DIR__.'/web/garantias.php';

// Administracion
require __DIR__.'/web/admin/admin.php';
