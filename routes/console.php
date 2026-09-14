<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

<<<<<<< HEAD
// Promociones: cada mañana revisa quién sigue esperando que se le pida
// la confirmación (autorizado por el asesor, sin contestar todavía).
Schedule::command('app:enviar-confirmaciones-promocion')->dailyAt('09:00');
=======
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
>>>>>>> 737af94976909b53781626ad9a03ea7f8344a55f
