<?php

use App\Http\Controllers\Mensajeria\MensajeriaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mensajería de WhatsApp
|--------------------------------------------------------------------------
| Bandeja de conversaciones con clientes por la Cloud API de Meta.
| Permisos: mensajeria.ver (leer) y mensajeria.responder (mandar).
| Lo que llega entra por el webhook de promociones.php (/webhooks/whatsapp).
*/
Route::middleware(['auth', 'verified', 'approved', 'can:mensajeria.ver'])
    ->prefix('mensajeria')->name('mensajeria.')
    ->group(function () {
        Route::get('/', [MensajeriaController::class, 'index'])->name('index');

        Route::get('/sync', [MensajeriaController::class, 'sync'])->name('sync');
        Route::get('/conversaciones', [MensajeriaController::class, 'conversaciones'])->name('conversaciones');
        Route::get('/conversaciones/{conversacion}/mensajes', [MensajeriaController::class, 'mensajes'])->name('mensajes');
        Route::get('/conversaciones/{conversacion}/contacto', [MensajeriaController::class, 'contacto'])->name('contacto');
        Route::post('/conversaciones/{conversacion}/leer', [MensajeriaController::class, 'leer'])->name('leer');
        Route::get('/archivo/{mensaje}', [MensajeriaController::class, 'media'])->name('media');
        Route::post('/archivo/{mensaje}/reintentar', [MensajeriaController::class, 'reintentarMedia'])->name('media.reintentar');
        Route::get('/plantillas', [MensajeriaController::class, 'plantillas'])->name('plantillas');

        Route::middleware('can:mensajeria.responder')->group(function () {
            Route::post('/conversaciones/{conversacion}/mensajes', [MensajeriaController::class, 'enviar'])
                ->middleware('throttle:60,1')->name('enviar');
            Route::post('/conversaciones/{conversacion}/plantilla', [MensajeriaController::class, 'enviarPlantilla'])
                ->middleware('throttle:30,1')->name('plantilla');
            Route::post('/mensajes/{mensaje}/reintentar', [MensajeriaController::class, 'reintentar'])->name('reintentar');
            Route::post('/nueva', [MensajeriaController::class, 'nueva'])->name('nueva');
        });
    });
