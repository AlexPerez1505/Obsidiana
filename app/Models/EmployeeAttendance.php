<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAttendance extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'validated_by',
        'attendance_date',
        'check_in',
        'check_out',
        'late_minutes',
        'worked_minutes',
        'punches_count',
        'modality',
        'incomplete',
        'status',
        'notes',
        'validated_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'validated_at' => 'datetime',
        'incomplete' => 'boolean',
    ];

    public const PUNTUAL = 'puntual';
    public const RETARDO = 'retardo';
    public const FALTA = 'falta';
    public const JUSTIFICADA = 'justificada';   // modalidad, vacaciones, permiso, incapacidad

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PUNTUAL => 'Puntual',
            self::RETARDO => 'Retardo',
            self::FALTA => 'Falta',
            self::JUSTIFICADA => 'Justificada',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusTono(): string
    {
        return match ($this->status) {
            self::PUNTUAL => 'badge--ok',
            self::RETARDO => 'badge--warn',
            self::FALTA => 'badge--danger',
            self::JUSTIFICADA => 'badge--info',
            default => '',
        };
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
