<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Asistencia\ChecadorApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST v1 (/api/v1)
|--------------------------------------------------------------------------
| Para apps (token de usuario por /auth/login) y para otros sistemas
| (llaves de integración creadas en Configuración → API e integraciones).
| Cada ruta pide el mismo permiso que su pantalla en la web (can:...), y
| el token puede recortarlo más. Documentación: /docs/api
|
| Este archivo solo carga los módulos de routes/api/v1/. No definas rutas
| de módulos aquí: agrégalas al archivo que corresponda.
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login')->name('auth.login');

    // Tienda en línea: pública, sin token (ver el archivo).
    require __DIR__.'/api/v1/tienda.php';

    Route::middleware(['auth:sanctum', 'api.cuenta', 'throttle:api'])->group(function () {
        Route::get('/auth/yo', [AuthController::class, 'yo'])->name('auth.yo');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/sesiones', [AuthController::class, 'sesiones'])->name('auth.sesiones');
        Route::delete('/auth/sesiones', [AuthController::class, 'cerrarOtras'])->name('auth.sesiones.cerrarOtras');
        Route::delete('/auth/sesiones/{sesion}', [AuthController::class, 'cerrarSesion'])
            ->whereNumber('sesion')->name('auth.sesiones.cerrar');

        require __DIR__.'/api/v1/clientes.php';
        require __DIR__.'/api/v1/ventas.php';
        require __DIR__.'/api/v1/inventario.php';
        require __DIR__.'/api/v1/almacen.php';
        require __DIR__.'/api/v1/logistica.php';
        require __DIR__.'/api/v1/rh.php';
        require __DIR__.'/api/v1/administracion.php';
        require __DIR__.'/api/v1/servicios.php';
    });
});

/*
|--------------------------------------------------------------------------
| API de dispositivos (sin sesión): agente del lector de huella
|--------------------------------------------------------------------------
| Autenticada con la llave del dispositivo (Bearer bk_…). Sin CSRF ni cookies.
*/
Route::middleware(['dispositivo', 'throttle:300,1'])->prefix('checador')->group(function () {
    Route::get('/ping', [ChecadorApiController::class, 'ping']);
    Route::get('/plantillas', [ChecadorApiController::class, 'plantillas']);
    Route::post('/checadas', [ChecadorApiController::class, 'checar']);
    Route::post('/no-reconocida', [ChecadorApiController::class, 'noReconocida']);

    Route::get('/inscripciones/pendiente', [ChecadorApiController::class, 'inscripcionPendiente']);
    Route::post('/inscripciones/{inscripcion}/avance', [ChecadorApiController::class, 'inscripcionAvance']);
    Route::post('/inscripciones/{inscripcion}/completar', [ChecadorApiController::class, 'inscripcionCompletar']);
    Route::post('/inscripciones/{inscripcion}/fallar', [ChecadorApiController::class, 'inscripcionFallar']);
});
