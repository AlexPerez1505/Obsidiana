<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un uso de cupón en un documento real.
 *
 *   reservado  → el cupón está en una cotización; aparta el uso hasta expira_en.
 *   consumido  → se registró la venta: cuenta para siempre.
 *   liberado   → la reserva venció, se quitó el cupón o se borró la cotización.
 *   revertido  → la venta se canceló o se eliminó; el uso vuelve a quedar disponible.
 *
 * Para los límites cuentan: consumidos + reservados no vencidos (scope vigentes).
 */
class CuponUso extends Model
{
    protected $table = 'mkt_cupon_usos';

    public const RESERVADO = 'reservado';
    public const CONSUMIDO = 'consumido';
    public const LIBERADO = 'liberado';
    public const REVERTIDO = 'revertido';

    protected $fillable = [
        'cupon_id', 'cliente_id', 'campana_id', 'destinatario_id', 'cotizacion_id', 'venta_id',
        'estado', 'subtotal_elegible', 'descuento', 'reservado_en', 'expira_en', 'consumido_en',
        'cerrado_en', 'motivo_cierre', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_elegible' => 'decimal:2',
            'descuento' => 'decimal:2',
            'reservado_en' => 'datetime',
            'expira_en' => 'datetime',
            'consumido_en' => 'datetime',
            'cerrado_en' => 'datetime',
        ];
    }

    public function scopeVigentes(Builder $q): Builder
    {
        return $q->where(function ($w) {
            $w->where('estado', self::CONSUMIDO)
                ->orWhere(fn ($r) => $r->where('estado', self::RESERVADO)->where('expira_en', '>', now()));
        });
    }

    public function cupon(): BelongsTo
    {
        return $this->belongsTo(Cupon::class, 'cupon_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class, 'campana_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function vigente(): bool
    {
        return $this->estado === self::CONSUMIDO
            || ($this->estado === self::RESERVADO && $this->expira_en && $this->expira_en->isFuture());
    }

    public function estadoLabel(): string
    {
        if ($this->estado === self::RESERVADO && ! $this->vigente()) {
            return 'Reserva vencida';
        }

        return [
            self::RESERVADO => 'Reservado', self::CONSUMIDO => 'Usado',
            self::LIBERADO => 'Liberado', self::REVERTIDO => 'Revertido',
        ][$this->estado] ?? $this->estado;
    }

    public function tono(): string
    {
        return match (true) {
            $this->estado === self::CONSUMIDO => 'ok',
            $this->estado === self::RESERVADO && $this->vigente() => 'info',
            $this->estado === self::REVERTIDO => 'danger',
            default => 'neutral',
        };
    }
}
