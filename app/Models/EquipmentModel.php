<?php

namespace App\Models;

use App\Support\Concerns\GuardaEnMayusculas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentModel extends Model
{
    use GuardaEnMayusculas;

    protected $fillable = ['brand_id', 'subtype_id', 'name', 'description'];

    /** El nombre del modelo se guarda siempre en mayúsculas. */
    protected array $mayusculas = ['name'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function subtype(): BelongsTo
    {
        return $this->belongsTo(Subtype::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'equipment_model_id');
    }
}
