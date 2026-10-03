<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Algo que le pasa a toda la empresa (o a un grupo) en ciertos días:
 *  - descanso: vacaciones del equipo, puente, cierre. No se espera a nadie.
 *  - sin_checar: se trabaja ese día pero fuera (comida, evento, salida de
 *    equipo): cuenta como asistencia y no se pide huella.
 *  - horario_especial: ese día el horario es otro (ej. 24 de diciembre 9–14).
 */
class CompanyEvent extends Model
{
    public const TIPOS = [
        'descanso' => 'Descanso / vacaciones del equipo',
        'sin_checar' => 'Día sin checar (comida, evento, salida del equipo)',
        'horario_especial' => 'Horario especial',
    ];

    protected $fillable = [
        'name', 'type', 'date_from', 'date_to', 'scope', 'user_ids',
        'time_in', 'time_out', 'tolerance_minutes', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date', 'user_ids' => 'array'];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->type] ?? ucfirst($this->type);
    }

    /** ¿Este evento le toca a este empleado? */
    public function aplicaA(int $userId): bool
    {
        return $this->scope === 'todos' || in_array($userId, array_map('intval', $this->user_ids ?? []), true);
    }

    public function cubre(\DateTimeInterface $fecha): bool
    {
        $f = $fecha->format('Y-m-d');

        return $this->date_from->toDateString() <= $f && $this->date_to->toDateString() >= $f;
    }
}
