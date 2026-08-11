<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPagoPlantilla extends Model
{
    protected $table = 'plan_pago_plantillas';

    protected $fillable = [
        'nombre',
        'numero_pagos',
        'dias_entre_pagos',
        'metodo_pago',
        'descripcion',
    ];

    protected $casts = [
        'numero_pagos' => 'integer',
        'dias_entre_pagos' => 'integer',
    ];
}
