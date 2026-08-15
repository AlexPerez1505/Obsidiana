<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Paquete extends Model
{
    protected $table = 'paquetes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'imagen',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'paquete_equipo')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    /**
     * Precio efectivo: el fijado, o la suma de sus equipos por cantidad.
     */
    public function precioCalculado(): float
    {
        if ((float) $this->precio > 0) {
            return (float) $this->precio;
        }

        return (float) $this->equipos->sum(fn ($e) => (float) $e->precio * (int) $e->pivot->cantidad);
    }
}
