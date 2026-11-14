<?php

/*
|--------------------------------------------------------------------------
| Agenda empresarial y personal
|--------------------------------------------------------------------------
| Las fechas y horas de la agenda se guardan como "hora de pared" de la
| empresa (no en UTC): un evento a las 10:00 es a las 10:00 en la oficina.
| Solo los momentos de envío de recordatorios se calculan en UTC.
*/
return [
    'timezone' => env('AGENDA_TIMEZONE', env('ASISTENCIA_TIMEZONE', 'America/Mexico_City')),

    // Hora a la que se avisa un seguimiento o tarea que tiene fecha pero no hora.
    'hora_aviso_sin_hora' => env('AGENDA_HORA_AVISO', '08:00'),

    // Hora del resumen diario (actividades del día, vencidos y próximos eventos).
    'hora_resumen' => env('AGENDA_HORA_RESUMEN', '07:30'),

    // Recordatorios que se pueden elegir (minutos antes). 0 = al momento.
    'recordatorios' => [0 => 'Al momento', 15 => '15 minutos antes', 60 => '1 hora antes', 1440 => '1 día antes'],

    // Por omisión, si no se elige nada.
    'recordatorio_default' => [15],

    // Repeticiones: se generan como ocurrencias reales hasta este límite.
    'recurrencia_max_ocurrencias' => 120,
    'recurrencia_max_meses' => 12,

    // Duración que se asume para revisar choques cuando no hay hora de fin.
    'duracion_default_min' => 60,
];
