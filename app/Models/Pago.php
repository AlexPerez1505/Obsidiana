<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'plan_pago_id',
        'cliente_id',
        'monto_pagado',
        'pago_atrasado',
        'pagado',
        'nota',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'pago_atrasado' => 'boolean',
        'pagado' => 'boolean',
    ];

    public function planPago(): BelongsTo
    {
        return $this->belongsTo(PlanPago::class, 'plan_pago_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(EvidenciaPago::class, 'pago_id');
    }
}
