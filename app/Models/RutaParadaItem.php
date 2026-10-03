<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutaParadaItem extends Model
{
    protected $table = 'ruta_parada_items';

    protected $fillable = ['ruta_parada_id', 'descripcion', 'cantidad', 'orden'];

    public function parada(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'ruta_parada_id');
    }
}
