<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un seguimiento programado con un cliente o prospecto: qué hay que hacer
 * y cuándo. El día que toca, el responsable recibe el aviso.
 */
class ClienteSeguimiento extends Model
{
    protected $table = 'cliente_seguimientos';

    public const TIPOS = [
        'llamada' => 'Llamarle',
        'cotizacion' => 'Hacerle cotización',
        'visita' => 'Visitarlo',
        'correo' => 'Escribirle',
        'demo' => 'Demostración de equipo',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'customer_id', 'user_id', 'tipo', 'fecha', 'nota',
        'notificado_en', 'hecho_en', 'hecho_por', 'resultado', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'notificado_en' => 'datetime',
            'hecho_en' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hechoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hecho_por');
    }

    public function scopePendientes(Builder $q): Builder
    {
        return $q->whereNull('hecho_en');
    }

    /** Los que ya tocan (hoy o antes) y todavía no se han avisado. */
    public function scopePorAvisar(Builder $q): Builder
    {
        return $q->pendientes()->whereNull('notificado_en')->whereDate('fecha', '<=', now()->toDateString());
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function hecho(): bool
    {
        return $this->hecho_en !== null;
    }

    public function vencido(): bool
    {
        return ! $this->hecho() && $this->fecha && $this->fecha->lt(now()->startOfDay());
    }

    public function esHoy(): bool
    {
        return ! $this->hecho() && $this->fecha && $this->fecha->isToday();
    }

    /** "Hoy", "Mañana", "Vencido hace 3 días", "En 12 días"... */
    public function cuando(): string
    {
        if ($this->hecho()) {
            return 'Hecho el '.$this->hecho_en->format('d/m/Y');
        }

        if (! $this->fecha) {
            return 'Sin fecha';
        }

        $dias = (int) now()->startOfDay()->diffInDays($this->fecha->startOfDay(), false);

        return match (true) {
            $dias === 0 => 'Hoy',
            $dias === 1 => 'Mañana',
            $dias === -1 => 'Vencido desde ayer',
            $dias < 0 => 'Vencido hace '.abs($dias).' días',
            default => 'En '.$dias.' días',
        };
    }
}
