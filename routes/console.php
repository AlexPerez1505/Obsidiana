<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
