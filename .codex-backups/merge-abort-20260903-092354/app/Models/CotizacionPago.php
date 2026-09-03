<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionPago extends Model
{
    protected $table = 'cotizacion_pagos';

    protected $fillable = [
        'cotizacion_id',
        'nombre',
        'fecha',
        'porcentaje',
        'monto',
        'bloqueado',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'porcentaje' => 'decimal:2',
            'monto' => 'decimal:2',
            'bloqueado' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
