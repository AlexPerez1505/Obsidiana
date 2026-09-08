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
