<?php

use App\Http\Controllers\Nomina\ConfiguracionNominaController;
use App\Http\Controllers\Nomina\NominaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Recursos Humanos · Nómina (sueldos, reglas, quincenas)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved', 'can:asistencia.nomina'])->prefix('rh/nomina')->name('nomina.')->group(function () {
    Route::get('/', [NominaController::class, 'index'])->name('index');
    Route::post('/cerrar', [NominaController::class, 'cerrar'])->name('cerrar');
    Route::post('/reabrir', [NominaController::class, 'reabrir'])->name('reabrir');
    Route::post('/pagada', [NominaController::class, 'pagada'])->name('pagada');
    Route::get('/exportar', [NominaController::class, 'exportar'])->name('exportar');
    Route::get('/historial', [NominaController::class, 'historial'])->name('historial');
    Route::post('/concepto-unico/{user}', [NominaController::class, 'conceptoUnico'])->name('concepto-unico');

    Route::get('/empleados', [ConfiguracionNominaController::class, 'empleados'])->name('empleados');
    Route::put('/empleados/{user}/sueldo', [ConfiguracionNominaController::class, 'guardarSueldo'])->name('sueldo');
    Route::post('/empleados/{user}/conceptos', [ConfiguracionNominaController::class, 'guardarConcepto'])->name('conceptos.store');
    Route::delete('/conceptos/{concepto}', [ConfiguracionNominaController::class, 'eliminarConcepto'])->name('conceptos.destroy');

    Route::get('/reglas', [ConfiguracionNominaController::class, 'reglas'])->name('reglas');
    Route::post('/reglas', [ConfiguracionNominaController::class, 'guardarRegla'])->name('reglas.store');
    Route::put('/reglas/{regla}', [ConfiguracionNominaController::class, 'guardarRegla'])->name('reglas.update');
    Route::post('/reglas/{regla}/alternar', [ConfiguracionNominaController::class, 'alternarRegla'])->name('reglas.alternar');
    Route::delete('/reglas/{regla}', [ConfiguracionNominaController::class, 'eliminarRegla'])->name('reglas.destroy');
    Route::post('/ajustes', [ConfiguracionNominaController::class, 'guardarAjustes'])->name('ajustes');
});
