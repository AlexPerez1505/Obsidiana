<?php

use App\Http\Controllers\Asistencia\ChecadorApiController;
use Illuminate\Support\Facades\Route;

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
