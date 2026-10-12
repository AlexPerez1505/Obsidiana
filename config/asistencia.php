<?php

return [
    // La app corre en UTC; las reglas de horario se evalúan en hora local.
    'timezone' => env('ASISTENCIA_TIMEZONE', 'America/Mexico_City'),

    // Horario base al dar de alta a un empleado
    'horario_base' => [
        'weekdays' => [1, 2, 3, 4, 5],
        'time_in' => '09:00',
        'time_out' => '18:00',
        'tolerancia_min' => 5,
    ],

    // Una huella repetida en menos de estos segundos se ignora (dedo puesto dos veces)
    'rebote_segundos' => 60,

    // Segundos que la pantalla del checador muestra el resultado
    'pantalla_segundos' => 3,

    // Un día "sin cerrar" (checada impar) se marca incompleto si pasaron estas horas
    'prenomina' => [
        'bono_puntualidad' => (float) env('ASISTENCIA_BONO_PUNTUALIDAD', 300),
        // Un día de sueldo = sueldo quincenal / días de la quincena
        'dias_quincena' => 15,
        // Descuento por retardo (0 = no se descuenta)
        'descuento_retardo' => (float) env('ASISTENCIA_DESCUENTO_RETARDO', 0),
        // Cuántos retardos equivalen a una falta (0 = nunca)
        'retardos_por_falta' => (int) env('ASISTENCIA_RETARDOS_POR_FALTA', 0),
    ],

    // Vigencia de una solicitud de registro de huella, en minutos
    'enrolamiento_minutos' => 15,
];
