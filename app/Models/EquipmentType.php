<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentType extends Model
{
    protected $fillable = ['name', 'description', 'requiere_emplayado'];

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
