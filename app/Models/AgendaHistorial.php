<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Bitácora inmutable de lo que le pasó a un seguimiento o actividad. */
class AgendaHistorial extends Model
{
    protected $table = 'agenda_historial';

    public const UPDATED_AT = null;

    protected $fillable = ['item_type', 'item_id', 'user_id', 'accion', 'antes', 'despues', 'motivo'];

    protected function casts(): array
    {
        return ['antes' => 'array', 'despues' => 'array', 'created_at' => 'datetime'];
    }

    public const ACCIONES = [
        'creado' => 'Creó',
        'editado' => 'Editó',
        'reprogramado' => 'Reprogramó',
        'reasignado' => 'Reasignó',
        'estado' => 'Cambió el estado',
        'completado' => 'Completó',
        'cancelado' => 'Canceló',
        'reabierto' => 'Reabrió',
        'respuesta' => 'Respondió la invitación',
        'importado' => 'Se incorporó a la agenda',
    ];

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function accionLabel(): string
    {
        return self::ACCIONES[$this->accion] ?? ucfirst($this->accion);
    }
}
