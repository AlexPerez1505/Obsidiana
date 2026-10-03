<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutaDesviacion extends Model
{
    protected $table = 'ruta_desviaciones';

    protected $fillable = ['ruta_id', 'lat', 'lng', 'max_distancia_m', 'inicio_en', 'fin_en'];

    protected function casts(): array
    {
        return ['inicio_en' => 'datetime', 'fin_en' => 'datetime', 'lat' => 'float', 'lng' => 'float'];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }

    public function abierta(): bool
    {
        return $this->fin_en === null;
    }
}
