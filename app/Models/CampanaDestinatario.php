<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una fila = un cliente al que le toca (o le tocó) una campaña. Es el
 * log real de envíos: sin esto no se podría saber a quién se le mandó
 * qué, ni por qué falló.
 */
class CampanaDestinatario extends Model
{
    protected $table = 'campana_destinatarios';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_ENVIADO = 'enviado';
    public const ESTADO_ENTREGADO = 'entregado';
    public const ESTADO_LEIDO = 'leido';
    public const ESTADO_FALLO = 'fallo';
    public const ESTADO_EXCLUIDO = 'excluido_sin_consentimiento';

    protected $fillable = [
        'campana_id',
        'cliente_id',
        'estado',
        'whatsapp_message_id',
        'enviado_en',
        'entregado_en',
        'leido_en',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'enviado_en' => 'datetime',
            'entregado_en' => 'datetime',
            'leido_en' => 'datetime',
        ];
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_ENVIADO => 'Enviado',
            self::ESTADO_ENTREGADO => 'Entregado',
            self::ESTADO_LEIDO => 'Leído',
            self::ESTADO_FALLO => 'Falló',
            self::ESTADO_EXCLUIDO => 'Excluido (sin consentimiento)',
            default => $this->estado,
        };
    }
}
