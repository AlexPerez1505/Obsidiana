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
        'modelo',
        'marca',
        'precio',
        'imagen',
        'descripcion',
        'sku',
        'activo',
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
