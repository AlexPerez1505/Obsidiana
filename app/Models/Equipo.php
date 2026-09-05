<?php

namespace App\Models;

use App\Models\Concerns\SincronizaNombresDeCatalogo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    /** Tipo, subtipo, marca y modelo salen del catálogo, igual que en Producto. */
    use SincronizaNombresDeCatalogo;

    protected $table = 'equipos';

    protected $fillable = [
        'equipment_type_id',
        'subtype_id',
        'brand_id',
        'equipment_model_id',
        'tipo',
        'subtipo',
        'modelo',
        'marca',
        'precio',
        'imagen',
        'descripcion',
        'sku',
        'activo',
    ];

    /** Equipo nombra "tipo" a lo que Producto llama "tipo_equipo". */
    protected function mapaDeCatalogo(): array
    {
        return [
            'equipment_type_id' => 'tipo',
            'subtype_id' => 'subtipo',
            'brand_id' => 'marca',
            'equipment_model_id' => 'modelo',
        ];
    }

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    public function subtipoCatalogo(): BelongsTo
    {
        return $this->belongsTo(Subtype::class, 'subtype_id');
    }

    public function marcaCatalogo(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function modeloCatalogo(): BelongsTo
    {
        return $this->belongsTo(EquipmentModel::class, 'equipment_model_id');
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
