<?php

use App\Http\Controllers\Api\V1\Rh\AsistenciaController;
use App\Http\Controllers\Api\V1\Rh\ChecadaController;
use App\Http\Controllers\Api\V1\Rh\DispositivoController;
use App\Http\Controllers\Api\V1\Rh\EmpleadoNominaController;
use App\Http\Controllers\Api\V1\Rh\EventoController;
use App\Http\Controllers\Api\V1\Rh\FestivoController;
use App\Http\Controllers\Api\V1\Rh\HorarioController;
use App\Http\Controllers\Api\V1\Rh\HuellaController;
use App\Http\Controllers\Api\V1\Rh\IncidenciaController;
use App\Http\Controllers\Api\V1\Rh\InscripcionController;
use App\Http\Controllers\Api\V1\Rh\MiAsistenciaController;
use App\Http\Controllers\Api\V1\Rh\QuincenaController;
use App\Http\Controllers\Api\V1\Rh\ReglaNominaController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — rh. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Mismos permisos que routes/web/asistencia.php y routes/web/nomina.php.
| La pantalla del checador (/checador/{token}) y la API del agente del
| lector (/api/checador/*) no son de aquí.
*/
Route::prefix('rh')->name('rh.')->group(function () {

    // Lo propio del empleado: sin permiso especial, solo lectura
    Route::get('/mi-asistencia', [MiAsistenciaController::class, 'resumen'])->name('mi-asistencia.resumen');
    Route::get('/mi-asistencia/checadas', [MiAsistenciaController::class, 'checadas'])->name('mi-asistencia.checadas');

    // ---------------- Asistencia: consulta ----------------
    Route::middleware('can:asistencia.ver')->group(function () {
        Route::get('/asistencia/dia', [AsistenciaController::class, 'dia'])->name('asistencia.dia');
        Route::get('/asistencia/indicadores', [AsistenciaController::class, 'indicadores'])->name('asistencia.indicadores');
        Route::get('/asistencia/catalogos', [AsistenciaController::class, 'catalogos'])->name('asistencia.catalogos');
        Route::get('/asistencia/empleados', [AsistenciaController::class, 'empleados'])->name('asistencia.empleados');
        Route::get('/asistencia/empleados/{user}', [AsistenciaController::class, 'empleado'])->whereNumber('user')->name('asistencia.empleado');

        Route::get('/asistencia/checadas', [ChecadaController::class, 'index'])->name('checadas.index');
        Route::get('/asistencia/checadas/{punch}', [ChecadaController::class, 'show'])->whereNumber('punch')->name('checadas.show');

        Route::get('/incidencias', [IncidenciaController::class, 'index'])->name('incidencias.index');
        Route::get('/incidencias/{modalidad}', [IncidenciaController::class, 'show'])->whereNumber('modalidad')->name('incidencias.show');
    });

    // ---------------- Asistencia: gestión ----------------
    Route::middleware('can:asistencia.gestionar')->group(function () {
        Route::post('/asistencia/checadas', [ChecadaController::class, 'store'])->name('checadas.store');
        Route::post('/asistencia/checadas/{punch}/anular', [ChecadaController::class, 'anular'])->whereNumber('punch')->name('checadas.anular');
        Route::post('/asistencia/cerrar-dia', [AsistenciaController::class, 'cerrarDia'])->name('asistencia.cerrar-dia');

        Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
        Route::post('/horarios/base', [HorarioController::class, 'base'])->name('horarios.base');
        Route::post('/horarios/masivo', [HorarioController::class, 'masivo'])->name('horarios.masivo');
        Route::get('/horarios/{user}', [HorarioController::class, 'show'])->whereNumber('user')->name('horarios.show');
        Route::put('/horarios/{user}', [HorarioController::class, 'update'])->whereNumber('user')->name('horarios.update');

        Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
        Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
        Route::get('/eventos/{evento}', [EventoController::class, 'show'])->whereNumber('evento')->name('eventos.show');
        Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])->whereNumber('evento')->name('eventos.destroy');

        Route::get('/festivos', [FestivoController::class, 'index'])->name('festivos.index');
        Route::post('/festivos', [FestivoController::class, 'store'])->name('festivos.store');
        Route::post('/festivos/oficiales', [FestivoController::class, 'oficiales'])->name('festivos.oficiales');
        Route::delete('/festivos/{festivo}', [FestivoController::class, 'destroy'])->whereNumber('festivo')->name('festivos.destroy');

        Route::post('/incidencias', [IncidenciaController::class, 'store'])->name('incidencias.store');
        Route::delete('/incidencias/{modalidad}', [IncidenciaController::class, 'destroy'])->whereNumber('modalidad')->name('incidencias.destroy');
    });

    // ---------------- Biometría (lectores y huellas) ----------------
    Route::middleware('can:asistencia.biometria')->prefix('biometria')->name('biometria.')->group(function () {
        Route::get('/dispositivos', [DispositivoController::class, 'index'])->name('dispositivos.index');
        Route::post('/dispositivos', [DispositivoController::class, 'store'])->name('dispositivos.store');
        Route::get('/dispositivos/{dispositivo}', [DispositivoController::class, 'show'])->whereNumber('dispositivo')->name('dispositivos.show');
        Route::post('/dispositivos/{dispositivo}/llave', [DispositivoController::class, 'llave'])->whereNumber('dispositivo')->name('dispositivos.llave');
        Route::post('/dispositivos/{dispositivo}/alternar', [DispositivoController::class, 'alternar'])->whereNumber('dispositivo')->name('dispositivos.alternar');

        Route::get('/inscripciones', [InscripcionController::class, 'index'])->name('inscripciones.index');
        Route::post('/inscripciones', [InscripcionController::class, 'store'])->name('inscripciones.store');
        Route::get('/inscripciones/{inscripcion}', [InscripcionController::class, 'show'])->whereNumber('inscripcion')->name('inscripciones.show');
        Route::post('/inscripciones/{inscripcion}/cancelar', [InscripcionController::class, 'cancelar'])->whereNumber('inscripcion')->name('inscripciones.cancelar');

        Route::get('/huellas', [HuellaController::class, 'index'])->name('huellas.index');
        Route::delete('/huellas/{huella}', [HuellaController::class, 'destroy'])->whereNumber('huella')->name('huellas.destroy');
    });

    // ---------------- Nómina (incluye sueldos) ----------------
    Route::middleware('can:asistencia.nomina')->prefix('nomina')->name('nomina.')->group(function () {
        Route::get('/quincena', [QuincenaController::class, 'show'])->name('quincena.show');
        Route::post('/quincena/cerrar', [QuincenaController::class, 'cerrar'])->name('quincena.cerrar');
        Route::post('/quincena/reabrir', [QuincenaController::class, 'reabrir'])->name('quincena.reabrir');
        Route::post('/quincena/pagada', [QuincenaController::class, 'pagada'])->name('quincena.pagada');
        Route::get('/quincena/exportar', [QuincenaController::class, 'exportar'])->name('quincena.exportar');
        Route::get('/historial', [QuincenaController::class, 'historial'])->name('historial');

        Route::get('/empleados', [EmpleadoNominaController::class, 'index'])->name('empleados.index');
        Route::get('/empleados/{user}', [EmpleadoNominaController::class, 'show'])->whereNumber('user')->name('empleados.show');
        Route::put('/empleados/{user}/sueldo', [EmpleadoNominaController::class, 'sueldo'])->whereNumber('user')->name('empleados.sueldo');
        Route::post('/empleados/{user}/conceptos', [EmpleadoNominaController::class, 'conceptoFijo'])->whereNumber('user')->name('conceptos.store');
        Route::post('/empleados/{user}/conceptos-unicos', [QuincenaController::class, 'conceptoUnico'])->whereNumber('user')->name('conceptos.unico');
        Route::delete('/conceptos/{concepto}', [EmpleadoNominaController::class, 'eliminarConcepto'])->whereNumber('concepto')->name('conceptos.destroy');

        Route::get('/reglas', [ReglaNominaController::class, 'index'])->name('reglas.index');
        Route::post('/reglas', [ReglaNominaController::class, 'store'])->name('reglas.store');
        Route::get('/reglas/{regla}', [ReglaNominaController::class, 'show'])->whereNumber('regla')->name('reglas.show');
        Route::put('/reglas/{regla}', [ReglaNominaController::class, 'update'])->whereNumber('regla')->name('reglas.update');
        Route::post('/reglas/{regla}/alternar', [ReglaNominaController::class, 'alternar'])->whereNumber('regla')->name('reglas.alternar');
        Route::delete('/reglas/{regla}', [ReglaNominaController::class, 'destroy'])->whereNumber('regla')->name('reglas.destroy');

        Route::get('/ajustes', [ReglaNominaController::class, 'ajustes'])->name('ajustes.show');
        Route::put('/ajustes', [ReglaNominaController::class, 'guardarAjustes'])->name('ajustes.update');
    });
});
