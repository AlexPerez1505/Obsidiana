<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutaEvento extends Model
{
    protected $table = 'ruta_eventos';

    protected $fillable = ['ruta_id', 'ruta_parada_id', 'user_id', 'tipo', 'detalle', 'lat', 'lng', 'ocurrio_en'];

    protected function casts(): array
    {
        return ['ocurrio_en' => 'datetime'];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }

    public function parada(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'ruta_parada_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
