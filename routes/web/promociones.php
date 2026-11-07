<?php

use App\Http\Controllers\Promociones\CampanaController;
use App\Http\Controllers\Promociones\CampanasApiController;
use App\Http\Controllers\Promociones\CuponesApiController;
use App\Http\Controllers\Promociones\ResumenController;
use App\Http\Controllers\Promociones\ConfiguracionController;
use App\Http\Controllers\Promociones\ConsentimientoController;
use App\Http\Controllers\Promociones\CuponController;
use App\Http\Controllers\Promociones\OfertaPublicaController;
use App\Http\Controllers\Promociones\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketing y promociones
|--------------------------------------------------------------------------
| Campañas por WhatsApp, cupones y la oferta que abre el cliente.
| Permisos: promociones.ver / promociones.gestionar / promociones.enviar /
| cupones.gestionar / promociones.consentimiento (ver CatalogoPermisos).
*/
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('promociones')->name('promociones.')
    ->group(function () {
        Route::get('/', ResumenController::class)->middleware('can:promociones.ver')->name('inicio');

        // ---------- Datos (JSON) de las pantallas ----------
        Route::middleware('can:promociones.ver')->prefix('/datos')->name('datos.')->group(function () {
            Route::get('/cupones', [CuponesApiController::class, 'index'])->name('cupones');
            Route::get('/cupones/{cupon}', [CuponesApiController::class, 'show'])->name('cupones.show');
            Route::get('/campanas', [CampanasApiController::class, 'index'])->name('campanas');
            Route::get('/campanas/calendario', [CampanasApiController::class, 'calendario'])->name('campanas.calendario');
            Route::get('/campanas/segmentos', [CampanasApiController::class, 'segmentos'])->name('campanas.segmentos');
            Route::get('/campanas/{campana}', [CampanasApiController::class, 'show'])->name('campanas.show');
        });

        // ---------- Campañas ----------
        Route::get('/campanas', [CampanaController::class, 'index'])->middleware('can:promociones.ver')->name('campanas.index');
        Route::post('/campanas/audiencia', [CampanaController::class, 'audiencia'])->middleware('can:promociones.gestionar')->name('campanas.audiencia');

        Route::middleware('can:promociones.gestionar')->group(function () {
            Route::get('/campanas/crear', [CampanaController::class, 'create'])->name('campanas.create');
            Route::post('/campanas', [CampanaController::class, 'store'])->name('campanas.store');
            Route::get('/campanas/{campana}/editar', [CampanaController::class, 'edit'])->name('campanas.edit');
            Route::put('/campanas/{campana}', [CampanaController::class, 'update'])->name('campanas.update');
            Route::post('/campanas/{campana}/duplicar', [CampanaController::class, 'duplicar'])->name('campanas.duplicar');
            Route::post('/campanas/{campana}/cancelar', [CampanaController::class, 'cancelar'])->name('campanas.cancelar');
        });

        Route::middleware('can:promociones.enviar')->group(function () {
            Route::post('/campanas/{campana}/prueba', [CampanaController::class, 'prueba'])->middleware('throttle:10,1')->name('campanas.prueba');
            Route::post('/campanas/{campana}/enviar', [CampanaController::class, 'enviar'])->name('campanas.enviar');
            Route::post('/campanas/{campana}/pausar', [CampanaController::class, 'pausar'])->name('campanas.pausar');
            Route::post('/campanas/{campana}/reanudar', [CampanaController::class, 'reanudar'])->name('campanas.reanudar');
        });

        Route::get('/campanas/{campana}', [CampanaController::class, 'show'])->middleware('can:promociones.ver')->name('campanas.show');

        // ---------- Cupones ----------
        Route::get('/cupones', [CuponController::class, 'index'])->middleware('can:promociones.ver')->name('cupones.index');

        // Lo usa el cotizador (cotizaciones y ventas): basta con poder cotizar o vender.
        Route::post('/cupones/comprobar', [CuponController::class, 'comprobar'])->middleware('throttle:60,1')->name('cupones.comprobar');

        Route::middleware('can:cupones.gestionar')->group(function () {
            Route::get('/cupones/crear', [CuponController::class, 'create'])->name('cupones.create');
            Route::get('/cupones/sugerir-codigo', [CuponController::class, 'sugerirCodigo'])->name('cupones.sugerir');
            Route::post('/cupones', [CuponController::class, 'store'])->name('cupones.store');
            Route::get('/cupones/{cupon}/editar', [CuponController::class, 'edit'])->name('cupones.edit');
            Route::put('/cupones/{cupon}', [CuponController::class, 'update'])->name('cupones.update');
            Route::post('/cupones/{cupon}/estado', [CuponController::class, 'estado'])->name('cupones.estado');
            Route::post('/cupones/{cupon}/duplicar', [CuponesApiController::class, 'duplicar'])->name('cupones.duplicar');
            Route::post('/cupones-lote', [CuponesApiController::class, 'lote'])->name('cupones.lote');
        });

        Route::get('/cupones/{cupon}', [CuponController::class, 'show'])->middleware('can:promociones.ver')->name('cupones.show');

        // ---------- Consentimiento (desde el perfil del cliente) ----------
        Route::middleware('can:promociones.consentimiento')->group(function () {
            Route::post('/clientes/{cliente}/consentimiento', [ConsentimientoController::class, 'otorgar'])->name('consentimiento.otorgar');
            Route::post('/clientes/{cliente}/baja', [ConsentimientoController::class, 'revocar'])->name('consentimiento.revocar');
        });

        Route::get('/configuracion', ConfiguracionController::class)->middleware('can:promociones.ver')->name('configuracion');
    });

/*
| Oferta pública: el enlace del botón de WhatsApp. Sin sesión.
*/
Route::middleware('throttle:promo-publica')->group(function () {
    Route::get('/p/{token}', [OfertaPublicaController::class, 'show'])->name('promo.oferta');
    Route::post('/p/{token}/calcular', [OfertaPublicaController::class, 'calcular'])->name('promo.calcular');
    Route::post('/p/{token}/confirmar', [OfertaPublicaController::class, 'confirmar'])->middleware('throttle:promo-confirmar')->name('promo.confirmar');
    Route::get('/p/{token}/baja', [OfertaPublicaController::class, 'baja'])->name('promo.baja');
    Route::post('/p/{token}/baja', [OfertaPublicaController::class, 'confirmarBaja'])->name('promo.baja.confirmar');
});

/*
| Webhook de WhatsApp (Meta llama directo): sin sesión ni CSRF; lo protege
| la firma X-Hub-Signature-256 que se valida en el controlador.
*/
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verificar'])->name('webhooks.whatsapp.verificar');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'recibir'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \App\Http\Middleware\ObsVivoTocar::class,
    ])
    ->middleware('throttle:600,1')
    ->name('webhooks.whatsapp.recibir');
