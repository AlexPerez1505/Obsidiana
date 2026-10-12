<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutaUbicacion extends Model
{
    protected $table = 'ruta_ubicaciones';

    public $timestamps = false;

    protected $fillable = ['ruta_id', 'vehicle_id', 'lat', 'lng', 'velocidad_kmh', 'precision_m', 'rumbo', 'registrada_en'];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'velocidad_kmh' => 'float',
            'registrada_en' => 'datetime',
        ];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }
}
