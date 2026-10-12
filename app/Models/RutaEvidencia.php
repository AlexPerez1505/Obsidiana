<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RutaEvidencia extends Model
{
    protected $table = 'ruta_evidencias';

    protected $fillable = ['ruta_parada_id', 'tipo', 'archivo', 'lat', 'lng', 'subida_por'];

    public function parada(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'ruta_parada_id');
    }

    public function url(): string
    {
        return Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->archivo);
    }
}
