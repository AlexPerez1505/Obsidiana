<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un intento de pedirle al cliente que confirme si quiere promociones,
 * y lo que contestó (si contestó). Ver migración para el detalle de
 * por qué existe además de "recibe_promocion".
 */
class PromoConfirmacion extends Model
{
    protected $table = 'promo_confirmaciones';

    protected $fillable = [
        'cliente_id',
        'canal',
        'mensaje_enviado_en',
        'whatsapp_message_id',
        'respuesta',
        'respuesta_texto_crudo',
        'respondido_en',
    ];

    protected function casts(): array
    {
        return [
            'mensaje_enviado_en' => 'datetime',
            'respondido_en' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function contestoQueSi(): bool
    {
        return $this->respuesta === 'si';
    }

    public function contestoQueNo(): bool
    {
        return $this->respuesta === 'no';
    }
}
