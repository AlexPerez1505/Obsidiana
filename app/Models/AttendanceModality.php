<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Por qué un empleado no checa (o checa distinto) un día o un rango de días. */
class AttendanceModality extends Model
{
    public const TIPOS = [
        'campo' => 'Trabajo en campo',
        'home_office' => 'Home office',
        'comision' => 'Comisión',
        'vacaciones' => 'Vacaciones',
        'incapacidad' => 'Incapacidad',
        'permiso' => 'Permiso',
    ];

    protected $fillable = [
        'user_id', 'type', 'date_from', 'date_to', 'requires_fingerprint',
        'time_start', 'time_end', 'destination', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date', 'requires_fingerprint' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->type] ?? ucfirst($this->type);
    }
}
