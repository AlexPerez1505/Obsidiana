<?php

use App\Http\Controllers\Commercial\CampanaController;
use App\Http\Controllers\Commercial\PromocionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Promociones
|--------------------------------------------------------------------------
|
| El consentimiento (quién autorizó, quién confirmó, quién se dio de
| baja) vive en PromocionController. El mensaje que de verdad se manda
| a los clientes (las campañas) vive en CampanaController.
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-comercial/promociones', [PromocionController::class, 'index'])
        ->name('commercial.promociones.index');

    Route::post('/gestion-comercial/promociones/clientes/{cliente}/autorizar', [PromocionController::class, 'autorizar'])
        ->name('commercial.promociones.autorizar');

    Route::post('/gestion-comercial/promociones/clientes/{cliente}/revocar', [PromocionController::class, 'revocar'])
        ->name('commercial.promociones.revocar');

    Route::post('/gestion-comercial/promociones/clientes/{cliente}/respuesta', [PromocionController::class, 'registrarRespuesta'])
        ->name('commercial.promociones.registrarRespuesta');

    Route::post('/gestion-comercial/promociones/enviar-confirmaciones', [PromocionController::class, 'enviarConfirmaciones'])
        ->name('commercial.promociones.enviarConfirmaciones');

    Route::get('/gestion-comercial/promociones/campanas', [CampanaController::class, 'index'])
        ->name('commercial.promociones.campanas.index');

    Route::get('/gestion-comercial/promociones/campanas/crear', [CampanaController::class, 'create'])
        ->name('commercial.promociones.campanas.create');

    Route::post('/gestion-comercial/promociones/campanas', [CampanaController::class, 'store'])
        ->name('commercial.promociones.campanas.store');

    Route::get('/gestion-comercial/promociones/campanas/{campana}', [CampanaController::class, 'show'])
        ->name('commercial.promociones.campanas.show');

    Route::post('/gestion-comercial/promociones/campanas/{campana}/lanzar', [CampanaController::class, 'lanzar'])
        ->name('commercial.promociones.campanas.lanzar');

    Route::delete('/gestion-comercial/promociones/campanas/{campana}', [CampanaController::class, 'cancelar'])
        ->name('commercial.promociones.campanas.cancelar');
});

/*
| Webhook: a donde llegara la respuesta del cliente cuando se conecte
| una cuenta real de WhatsApp Business API. Sin sesion (los proveedores
| llaman esta URL directo, no como el usuario logueado), asi que no
| lleva el middleware de arriba.
*/
Route::post('/webhooks/whatsapp', [PromocionController::class, 'webhookEntrante'])
    ->name('webhooks.whatsapp');
