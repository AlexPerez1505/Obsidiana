<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    protected $table = 'equipos';

    protected $fillable = [
        'tipo',
        'subtipo',
        'modelo',
        'marca',
        'serie',
        'precio',
        'imagen',
        'descripcion',
        'observaciones',
        'sku',
        'activo',
        'evidencia_2_path',
        'evidencia_3_path',
        'video_path',
        'firma',
        'externo_interno',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function fichas(): HasMany
    {
        return $this->hasMany(FichaTecnica::class);
    }

    public function paquetes(): BelongsToMany
    {
        return $this->belongsToMany(Paquete::class, 'paquete_equipo')
            ->withPivot('cantidad')
            ->withTimestamps();
    }
}
