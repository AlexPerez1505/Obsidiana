<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = ['name', 'description'];

    public function equipmentModels(): HasMany
    {
        return $this->hasMany(EquipmentModel::class);
    }

    /** Subtipos en los que se ofrece esta marca. */
    public function subtypes(): BelongsToMany
    {
        return $this->belongsToMany(Subtype::class, 'brand_subtype')->withTimestamps();
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'brand_id');
    }
}
