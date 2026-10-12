<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un toque del lector. No se edita: una checada equivocada se anula
 * (voided_at) y queda el registro de quién y por qué.
 */
class AttendancePunch extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'device_id', 'punched_at', 'method', 'confidence', 'kind', 'seq',
        'work_date', 'note', 'created_by', 'voided_at', 'voided_by', 'void_reason',
    ];

    protected function casts(): array
    {
        return ['punched_at' => 'datetime', 'work_date' => 'date', 'voided_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function creadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeValidas(Builder $q): Builder
    {
        return $q->whereNull('voided_at');
    }

    /** Hora local de la checada (la app guarda en UTC). */
    public function horaLocal(): \Illuminate\Support\Carbon
    {
        return $this->punched_at->copy()->setTimezone(config('asistencia.timezone'));
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'regreso' => 'Regreso',
            default => 'Checada',
        };
    }
}
