<?php

/*
|--------------------------------------------------------------------------
| Reglas de comisión (sobre lo vendido, sin IVA)
|--------------------------------------------------------------------------
| • Venta normal: el vendedor gana "endoscopia" % de lo que sea de
|   endoscopia y "general" % de todo lo demás.
| • Venta de congreso (la venta trae congreso, o el cliente se registró en
|   un congreso): "congreso" % del total, repartido en partes iguales entre
|   los usuarios asignados a ese congreso (los que fueron).
*/
return [
    'endoscopia' => (float) env('COMISION_ENDOSCOPIA', 3),
    'general' => (float) env('COMISION_GENERAL', 4),
    'congreso' => (float) env('COMISION_CONGRESO', 2),

    // Un renglón es de endoscopia si su tipo de equipo contiene este texto.
    'palabra_endoscopia' => env('COMISION_PALABRA_ENDOSCOPIA', 'ENDOSCOP'),
];
