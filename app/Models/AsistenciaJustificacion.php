<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lo que RH le pidió a alguien que justifique de un día (no registró
 * entrada ni salida, o no registró salida) y lo que esa persona respondió.
 */
class AsistenciaJustificacion extends Model
{
    protected $table = 'asistencia_justificaciones';

    public const PENDIENTE = 'pendiente';
    public const RESPONDIDA = 'respondida';
    public const ACEPTADA = 'aceptada';
    public const RECHAZADA = 'rechazada';

    public const ESTADOS = [
        self::PENDIENTE => 'Esperando respuesta',
        self::RESPONDIDA => 'Respondió · por revisar',
        self::ACEPTADA => 'Justificación aceptada',
        self::RECHAZADA => 'Justificación rechazada',
    ];

    protected $fillable = [
        'user_id', 'work_date', 'problema', 'detalle', 'solicitada_por',
        'estado', 'motivo', 'hora_entrada', 'hora_salida', 'respondida_en',
        'revisada_por', 'revisada_en', 'nota_revision',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'respondida_en' => 'datetime',
            'revisada_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitada_por');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }

    /** Se puede responder mientras RH no la haya revisado. */
    public function abierta(): bool
    {
        return in_array($this->estado, [self::PENDIENTE, self::RESPONDIDA], true);
    }
}
