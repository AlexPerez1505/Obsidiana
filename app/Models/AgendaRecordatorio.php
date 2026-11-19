<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un aviso programado para un usuario sobre un seguimiento o actividad.
 * Hay una sola fila por (item, usuario, tipo, minutos): reprogramar mueve
 * la misma fila y reintentar no duplica.
 */
class AgendaRecordatorio extends Model
{
    protected $table = 'agenda_recordatorios';

    protected $fillable = ['item_type', 'item_id', 'user_id', 'tipo', 'minutos_antes', 'enviar_en', 'estado', 'enviado_en'];

    protected function casts(): array
    {
        return ['enviar_en' => 'datetime', 'enviado_en' => 'datetime'];
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
