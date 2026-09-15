<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromocionEnvio extends Model
{
    protected $table = 'promocion_envios';

    protected $fillable = [
        'promocion_id',
        'cliente_id',
        'canal',
        'destino_usado',
        'estado',
        'referencia_externa',
        'error_detalle',
    ];

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class, 'promocion_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }
}
