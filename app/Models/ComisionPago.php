<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un pago de comisión a un asesor por un mes concreto.
 *
 * No se edita: si se capturó mal, se elimina y se registra de nuevo,
 * igual que los cobros, para que el historial cuadre siempre.
 */
class ComisionPago extends Model
{
    protected $table = 'comision_pagos';

    protected $fillable = [
        'user_id', 'periodo', 'monto', 'fecha', 'base', 'nota', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
