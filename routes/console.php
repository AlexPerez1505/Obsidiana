<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Promociones: cada mañana revisa quién sigue esperando que se le pida
// la confirmación (autorizado por el asesor, sin contestar todavía).
Schedule::command('app:enviar-confirmaciones-promocion')->dailyAt('09:00');
/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
| Requieren que en el servidor corra `php artisan schedule:run` cada
| minuto (cron en Linux, Programador de tareas en Windows). Como respaldo,
| los recordatorios de seguimiento también se disparan desde el sistema
| cuando alguien lo usa (ver App\Services\Seguimientos::recordarSiToca).
*/
Schedule::command('seguimientos:recordar')->dailyAt('08:00');

// Congresos: pide cierre de los que ya terminaron y siguen con piezas
// marcadas como que están allá (insiste cada semana mientras quede algo).
// Respaldo: App\Services\RegresoDeCongresos::avisarSiToca, que se dispara
// al abrir la pantalla de Congresos.
Schedule::command('congresos:avisar-regresos')->dailyAt('09:30');
