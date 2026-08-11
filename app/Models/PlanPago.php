<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPago extends Model
{
    protected $table = 'plan_pagos';

    protected $fillable = [
        'nombre',
        'no_pago',
        'cliente_id',
        'cotizacion_id',
        'plazo_pagar',
        'metodo_pago',
        'monto',
    ];

    protected $casts = [
        'no_pago' => 'integer',
        'plazo_pagar' => 'date',
        'monto' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'plan_pago_id');
    }
}
