<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentaPago extends Model
{
    protected $table = 'venta_pagos';

    protected $fillable = [
        'venta_id', 'nombre', 'fecha', 'porcentaje', 'monto', 'bloqueado', 'orden',
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

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Cobros que se aplicaron (total o parcialmente) a esta parcialidad.
     * El pivote trae cuánto de cada cobro cayó aquí: un cobro de $1,000
     * puede cubrir un pago completo y parte del siguiente.
     */
    public function cobros(): BelongsToMany
    {
        return $this->belongsToMany(Cobro::class, 'cobro_aplicaciones', 'venta_pago_id', 'cobro_id')->withPivot('monto');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(CobroAplicacion::class);
    }

    /** Lo que de verdad se le ha aplicado (suma del pivote, no de los cobros completos). */
    public function cobrado(): float
    {
        return round((float) $this->cobros->sum(fn ($c) => (float) $c->pivot->monto), 2);
    }

    public function saldo(): float
    {
        return max(0, round((float) $this->monto - $this->cobrado(), 2));
    }

    public function estado(): string
    {
        $cobrado = $this->cobrado();

        return match (true) {
            $cobrado <= 0 => $this->vencido() ? 'vencido' : 'pendiente',
            $this->saldo() <= 0.009 => 'pagado',
            default => 'parcial',
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado()) {
            'pagado' => 'Pagado',
            'parcial' => 'Parcial',
            'vencido' => 'Vencido',
            default => 'Pendiente',
        };
    }

    /**
     * Se pasó la fecha y sigue sin cobrarse. El día que vence todavía no
     * cuenta como atrasado (isPast() la marcaría vencida desde la
     * madrugada del mismo día); tiene que pasar al menos un día completo.
     */
    public function vencido(): bool
    {
        return $this->fecha
            && $this->fecha->lt(now()->startOfDay())
            && $this->saldo() > 0.009;
    }

    /**
     * Una parcialidad con dinero encima no se puede borrar ni dejar por
     * debajo de lo ya cobrado: el recibo que tiene el cliente dejaría de
     * cuadrar con el sistema.
     */
    public function tieneCobros(): bool
    {
        return $this->cobros()->exists();
    }
}
