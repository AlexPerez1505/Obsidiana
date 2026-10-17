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

// Asistencia: cierra el día anterior y genera las faltas automáticas (hora local de la empresa).
// Respaldo: ServicioDeAsistencia::cerrarPendientes al abrir el panel de asistencia.
Schedule::command('asistencia:cerrar')->dailyAt('00:30')->timezone(config('asistencia.timezone'));

// Garantías: primero se ponen al día con las ventas (por si alguna entrega
// no pasó por los eventos) y luego se avisa de vencimientos, reclamaciones
// atrasadas y préstamos sin regresar.
Schedule::command('garantias:sincronizar')->dailyAt('06:30');
Schedule::command('garantias:avisar')->dailyAt('08:15');
