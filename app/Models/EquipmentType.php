<?php

namespace App\Models;

use App\Support\Concerns\GuardaEnMayusculas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentType extends Model
{
    use GuardaEnMayusculas;

    protected $fillable = ['name', 'description', 'requiere_emplayado'];

    /** El nombre del tipo de equipo se guarda siempre en mayúsculas. */
    protected array $mayusculas = ['name'];

    protected $casts = [
        // Al vender, dice si las partidas de este tipo se emplayan antes de salir.
        'requiere_emplayado' => 'boolean',
    ];

    public function subtypes(): HasMany
    {
        return $this->hasMany(Subtype::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
