<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaqueteriaGuia extends Model
{
    protected $table = 'paqueteria_guias';

    protected $fillable = [
        'paqueteria', 'numero_guia', 'capacidad_kg', 'estado', 'envio_id', 'usada_at',
    ];

    protected function casts(): array
    {
        return [
            'capacidad_kg' => 'decimal:2',
            'usada_at' => 'datetime',
        ];
    }

    public function envio(): BelongsTo
    {
        return $this->belongsTo(PaqueteriaEnvio::class, 'envio_id');
    }
}
