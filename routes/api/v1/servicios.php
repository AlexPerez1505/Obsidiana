<?php

use App\Http\Controllers\Api\V1\Marketing\BibliotecaController;
use App\Http\Controllers\Api\V1\Marketing\GuiaDeMarcaController;
use App\Http\Controllers\Api\V1\Marketing\TableroController;
use App\Http\Controllers\Api\V1\Marketing\TareaController;
use App\Http\Controllers\Api\V1\Servicios\GarantiaController;
use App\Http\Controllers\Api\V1\Servicios\InvitacionController;
use App\Http\Controllers\Api\V1\Servicios\MantenimientoController;
use App\Http\Controllers\Api\V1\Servicios\ServicioController;
use App\Http\Controllers\Api\V1\Servicios\TecnicoExternoController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — servicios. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Gestión de servicios y Marketing. Los permisos son los mismos de las
| rutas web (routes/web/services/historial.php y
| routes/web/marketing/marketing.php): todo pide el de "ver" del módulo y
| cada acción el suyo encima.
|
| Fuera a propósito: los flujos públicos por enlace o QR (/nueva-orden,
| /qr, /reporte-equipo, /api/qr/generar). Funcionan sin sesión con su
| propio token y no tienen nada que ver con un token de la API.
*/

Route::prefix('servicios')->name('servicios.')->middleware('can:servicios.ver')->group(function () {
    Route::get('/', [ServicioController::class, 'index'])->name('index');
    Route::post('/', [ServicioController::class, 'store'])
        ->middleware('can:servicios.crear')->name('store');
    Route::get('/catalogos', [ServicioController::class, 'catalogos'])
        ->middleware('can:servicios.crear')->name('catalogos');
    Route::get('/aprobaciones', [ServicioController::class, 'aprobaciones'])->name('aprobaciones');

    Route::post('/invitaciones', [InvitacionController::class, 'store'])
        ->middleware('can:servicios.crear')->name('invitaciones.store');

    Route::get('/tecnicos-externos', [TecnicoExternoController::class, 'index'])
        ->middleware('can:servicios.crear')->name('tecnicos_externos.index');
    Route::post('/tecnicos-externos', [TecnicoExternoController::class, 'store'])
        ->middleware('can:servicios.crear')->name('tecnicos_externos.store');

    Route::get('/garantias', [GarantiaController::class, 'index'])->name('garantias.index');
    Route::post('/garantias', [GarantiaController::class, 'store'])
        ->middleware('can:servicios.crear')->name('garantias.store');
    Route::get('/garantias/{garantia}', [GarantiaController::class, 'show'])
        ->whereNumber('garantia')->name('garantias.show');

    Route::get('/mantenimiento', [MantenimientoController::class, 'index'])->name('mantenimiento.index');
    Route::get('/mantenimiento/tecnicos', [MantenimientoController::class, 'tecnicos'])->name('mantenimiento.tecnicos');
    Route::get('/mantenimiento/{service}/reporte', [MantenimientoController::class, 'reporte'])
        ->whereNumber('service')->name('mantenimiento.reporte');
    Route::put('/mantenimiento/{service}/reporte', [MantenimientoController::class, 'guardarReporte'])
        ->whereNumber('service')->middleware('can:servicios.crear')->name('mantenimiento.reporte.guardar');

    Route::get('/{service}', [ServicioController::class, 'show'])
        ->whereNumber('service')->name('show');
    Route::post('/{service}/aprobar', [ServicioController::class, 'aprobar'])
        ->whereNumber('service')->middleware('can:servicios.crear')->name('aprobar');
    Route::post('/{service}/denegar', [ServicioController::class, 'denegar'])
        ->whereNumber('service')->middleware('can:servicios.crear')->name('denegar');
    Route::post('/{service}/renovar-qr', [ServicioController::class, 'renovarQr'])
        ->whereNumber('service')->middleware('can:servicios.crear')->name('renovar_qr');
});

Route::prefix('marketing')->name('marketing.')->middleware('can:marketing.ver')->group(function () {
    Route::get('/inicio', [TableroController::class, 'inicio'])->name('inicio');
    Route::get('/calendario', [TableroController::class, 'calendario'])->name('calendario');
    Route::get('/aprobacion-flyers', [TableroController::class, 'aprobacionFlyers'])
        ->middleware('can:marketing.aprobar')->name('aprobacion_flyers');

    Route::get('/tareas', [TareaController::class, 'index'])->name('tareas.index');
    Route::get('/tareas/opciones', [TareaController::class, 'opciones'])->name('tareas.opciones');
    Route::post('/tareas', [TareaController::class, 'store'])
        ->middleware('can:marketing.editar')->name('tareas.store');
    Route::post('/tareas/preview-link', [TareaController::class, 'previewLink'])
        ->middleware('can:marketing.editar')->name('tareas.preview_link');
    Route::get('/tareas/{task}', [TareaController::class, 'show'])
        ->whereNumber('task')->name('tareas.show');
    Route::put('/tareas/{task}', [TareaController::class, 'update'])
        ->whereNumber('task')->middleware('can:marketing.editar')->name('tareas.update');
    Route::delete('/tareas/{task}', [TareaController::class, 'destroy'])
        ->whereNumber('task')->middleware('can:marketing.editar')->name('tareas.destroy');
    Route::post('/tareas/{task}/enviar-revision', [TareaController::class, 'enviarRevision'])
        ->whereNumber('task')->middleware('can:marketing.editar')->name('tareas.enviar_revision');
    Route::post('/tareas/{task}/aprobar', [TareaController::class, 'aprobar'])
        ->whereNumber('task')->middleware('can:marketing.aprobar')->name('tareas.aprobar');
    Route::post('/tareas/{task}/devolver', [TareaController::class, 'devolver'])
        ->whereNumber('task')->middleware('can:marketing.aprobar')->name('tareas.devolver');

    Route::get('/biblioteca', [BibliotecaController::class, 'index'])->name('biblioteca.index');
    Route::get('/biblioteca/{task}/descargar', [BibliotecaController::class, 'descargar'])
        ->whereNumber('task')->name('biblioteca.descargar');

    Route::get('/guia-de-marca', [GuiaDeMarcaController::class, 'show'])->name('guia_de_marca.show');
    Route::put('/guia-de-marca', [GuiaDeMarcaController::class, 'update'])
        ->middleware('can:marketing.editar')->name('guia_de_marca.update');
});
