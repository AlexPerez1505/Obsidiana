<?php

use App\Http\Controllers\Asistencia\AsistenciaController;
use App\Http\Controllers\Asistencia\BiometriaController;
use App\Http\Controllers\Asistencia\ConfiguracionAsistenciaController;
use App\Http\Controllers\Asistencia\JustificacionesController;
use App\Http\Controllers\Asistencia\PantallaChecadorController;
use App\Http\Controllers\Asistencia\PrenominaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pantalla del checador (kiosko): sin sesión, se abre con el token del lector
|--------------------------------------------------------------------------
*/
Route::get('/checador/{token}', [PantallaChecadorController::class, 'pantalla'])->name('checador.pantalla');
Route::get('/checador/{token}/feed', [PantallaChecadorController::class, 'feed'])->middleware('throttle:240,1')->name('checador.feed');

/*
|--------------------------------------------------------------------------
| Recursos Humanos · Asistencia
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->prefix('rh/asistencia')->name('asistencia.')->group(function () {
    // Cada quien justifica su propio día desde el aviso: no necesita permisos de RH.
    Route::get('/justificar/{justificacion}', [JustificacionesController::class, 'mostrar'])->name('justificar');
    Route::post('/justificar/{justificacion}', [JustificacionesController::class, 'responder'])->name('justificar.responder');

    Route::middleware('can:asistencia.ver')->group(function () {
        Route::get('/', [AsistenciaController::class, 'panel'])->name('panel');
        Route::get('/checadas', [AsistenciaController::class, 'checadas'])->name('checadas');
        Route::get('/empleado/{user}', [AsistenciaController::class, 'empleado'])->name('empleado');
        Route::get('/incidencias', [ConfiguracionAsistenciaController::class, 'modalidades'])->name('modalidades');
    });

    Route::middleware('can:asistencia.gestionar')->group(function () {
        Route::post('/checadas/manual', [AsistenciaController::class, 'checadaManual'])->name('checadas.manual');
        Route::post('/checadas/{punch}/anular', [AsistenciaController::class, 'anular'])->name('checadas.anular');
        Route::post('/checadas/justificar', [JustificacionesController::class, 'pedir'])->name('justificaciones.pedir');
        Route::post('/justificaciones/{justificacion}/aceptar', [JustificacionesController::class, 'aceptar'])->name('justificaciones.aceptar');
        Route::post('/justificaciones/{justificacion}/rechazar', [JustificacionesController::class, 'rechazar'])->name('justificaciones.rechazar');
        Route::post('/cerrar-dia', [AsistenciaController::class, 'cerrar'])->name('cerrar');

        Route::get('/horarios', [ConfiguracionAsistenciaController::class, 'horarios'])->name('horarios');
        Route::post('/horarios/base', [ConfiguracionAsistenciaController::class, 'aplicarBase'])->name('horarios.base');
        Route::post('/horarios/masivo', [ConfiguracionAsistenciaController::class, 'guardarHorarioMasivo'])->name('horarios.masivo');
        Route::get('/eventos', [ConfiguracionAsistenciaController::class, 'eventos'])->name('eventos');
        Route::post('/eventos', [ConfiguracionAsistenciaController::class, 'guardarEvento'])->name('eventos.store');
        Route::put('/eventos/{evento}', [ConfiguracionAsistenciaController::class, 'actualizarEvento'])->name('eventos.update');
        Route::delete('/eventos/{evento}', [ConfiguracionAsistenciaController::class, 'eliminarEvento'])->withoutMiddleware('can:asistencia.gestionar')->middleware('can:asistencia.eliminar')->name('eventos.destroy');
        Route::put('/horarios/{user}', [ConfiguracionAsistenciaController::class, 'guardarHorario'])->name('horarios.guardar');
        Route::post('/festivos/oficiales', [ConfiguracionAsistenciaController::class, 'cargarFestivosOficiales'])->name('festivos.oficiales');
        Route::post('/festivos', [ConfiguracionAsistenciaController::class, 'guardarFestivo'])->name('festivos.store');
        Route::delete('/festivos/{festivo}', [ConfiguracionAsistenciaController::class, 'eliminarFestivo'])->withoutMiddleware('can:asistencia.gestionar')->middleware('can:asistencia.eliminar')->name('festivos.destroy');

        Route::post('/incidencias', [ConfiguracionAsistenciaController::class, 'guardarModalidad'])->name('modalidades.store');
        Route::delete('/incidencias/{modalidad}', [ConfiguracionAsistenciaController::class, 'eliminarModalidad'])->withoutMiddleware('can:asistencia.gestionar')->middleware('can:asistencia.eliminar')->name('modalidades.destroy');
    });

    Route::middleware('can:asistencia.biometria')->prefix('biometria')->name('biometria.')->group(function () {
        Route::get('/', [BiometriaController::class, 'index'])->name('index');
        Route::get('/estado', [BiometriaController::class, 'estadoInscripciones'])->name('estado');
        Route::post('/dispositivos', [BiometriaController::class, 'guardarDispositivo'])->name('dispositivos.store');
        Route::post('/dispositivos/{dispositivo}/llave', [BiometriaController::class, 'regenerarLlave'])->name('dispositivos.llave');
        Route::post('/dispositivos/{dispositivo}/alternar', [BiometriaController::class, 'alternarDispositivo'])->name('dispositivos.alternar');
        Route::post('/empleados/{user}/registrar', [BiometriaController::class, 'solicitarRegistro'])->name('registrar');
        Route::post('/inscripciones/{inscripcion}/cancelar', [BiometriaController::class, 'cancelarRegistro'])->name('inscripciones.cancelar');
        Route::delete('/huellas/{huella}', [BiometriaController::class, 'eliminarHuella'])->middleware('can:asistencia.biometria_eliminar')->name('huellas.destroy');
    });

});
