<?php

/*
|--------------------------------------------------------------------------
| Garantías
|--------------------------------------------------------------------------
| Parámetros del módulo de garantías. Los plazos de cobertura NO viven aquí:
| salen de cada venta y de las coberturas configuradas en pantalla.
*/

return [

    // Una garantía entra al semáforo "próxima a vencer" con estos días o menos.
    'dias_por_vencer' => (int) env('GARANTIAS_DIAS_POR_VENCER', 30),

    // Días antes del vencimiento en que se avisa al responsable (campana).
    'avisos_vencimiento' => [60, 30, 15, 7],

    // Gastos de una reclamación por encima de este monto (MXN) necesitan
    // que alguien con permiso de aprobar los autorice.
    'limite_aprobacion' => (float) env('GARANTIAS_LIMITE_APROBACION', 5000),

    // Horas para dar la primera respuesta al cliente.
    'horas_primera_respuesta' => 24,

    // Horas para cerrar una reclamación, según su prioridad.
    'horas_resolucion' => [
        'critica' => 24,
        'alta' => 72,
        'media' => 120,
        'baja' => 240,
    ],

];
