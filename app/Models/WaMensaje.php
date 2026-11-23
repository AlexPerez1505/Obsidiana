<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un mensaje de WhatsApp, entrante o saliente. */
class WaMensaje extends Model
{
    protected $table = 'wa_mensajes';

    public const ENTRANTE = 'entrante';
    public const SALIENTE = 'saliente';

    /** El estado solo avanza: un aviso que llega tarde no lo regresa. */
    public const RANGO = ['enviando' => 0, 'enviado' => 1, 'entregado' => 2, 'leido' => 3];

    protected $fillable = [
        'conversacion_id', 'wamid', 'uuid', 'direccion', 'tipo', 'cuerpo', 'datos', 'responde_a',
        'media_id', 'media_mime', 'media_nombre', 'media_tamano', 'media_path', 'media_estado',
        'estado', 'error_codigo', 'error', 'enviado_por', 'momento', 'entregado_en', 'leido_en',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'media_tamano' => 'integer',
            'momento' => 'datetime',
            'entregado_en' => 'datetime',
            'leido_en' => 'datetime',
        ];
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(WaConversacion::class, 'conversacion_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function tieneMedia(): bool
    {
        return in_array($this->tipo, ['image', 'video', 'audio', 'sticker', 'document'], true);
    }
}
