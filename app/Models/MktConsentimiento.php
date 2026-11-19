<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historial de cuándo y cómo un cliente aceptó o dejó de recibir promociones. */
class MktConsentimiento extends Model
{
    protected $table = 'mkt_consentimientos';

    public const UPDATED_AT = null;

    protected $fillable = ['cliente_id', 'accion', 'fuente', 'nota', 'user_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fuenteLabel(): string
    {
        return [
            'panel' => 'Registrado en el sistema',
            'enlace' => 'Desde el enlace de la promoción',
            'whatsapp' => 'Respuesta por WhatsApp',
        ][$this->fuente] ?? $this->fuente;
    }
}
