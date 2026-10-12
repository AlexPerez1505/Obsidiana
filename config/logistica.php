<?php

return [
    // Con llave de Google se usan Routes + Geocoding (tráfico real).
    // Sin llave el módulo funciona igual con cálculo local y OpenStreetMap.
    'google_key' => env('GOOGLE_MAPS_API_KEY'),

    // Punto de salida por defecto al crear una ruta
    'origen' => [
        'nombre' => env('LOGISTICA_ORIGEN_NOMBRE', 'Bodega Grupo MediBuy'),
        'direccion' => env('LOGISTICA_ORIGEN_DIRECCION', 'Toluca, Estado de México'),
        'lat' => (float) env('LOGISTICA_ORIGEN_LAT', 19.2826),
        'lng' => (float) env('LOGISTICA_ORIGEN_LNG', -99.6557),
    ],

    // Cada cuántos segundos manda posición el teléfono del chofer
    'intervalo_gps_s' => (int) env('LOGISTICA_INTERVALO_GPS', 15),

    // A cuántos metros del trazo planeado se considera desviación. Solo se
    // vigila cuando el trazo viene de Google (el local son líneas rectas).
    'desviacion_m' => (int) env('LOGISTICA_DESVIACION_M', 400),
    // Lecturas GPS seguidas fuera del trazo antes de abrir la desviación
    'desviacion_lecturas' => 3,

    // Velocidad media para estimar tiempos cuando no hay Google (km/h) y
    // factor para pasar de línea recta a distancia por carretera
    'velocidad_local_kmh' => 40,
    'factor_carretera' => 1.3,
];
