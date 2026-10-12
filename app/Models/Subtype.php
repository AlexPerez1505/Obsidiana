<?php

namespace App\Models;

use App\Support\Concerns\GuardaEnMayusculas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subtype extends Model
{
    use GuardaEnMayusculas;

    protected $fillable = ['equipment_type_id', 'name', 'description'];

    /** El nombre del subtipo se guarda siempre en mayúsculas. */
    protected array $mayusculas = ['name'];

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    /** Marcas declaradas para este subtipo. */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_subtype')->withTimestamps();
    }

    public function equipmentModels(): HasMany
    {
        return $this->hasMany(EquipmentModel::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'subtype_id');
    }
}
