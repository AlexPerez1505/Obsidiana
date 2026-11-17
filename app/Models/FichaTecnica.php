<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FichaTecnica extends Model
{
    protected $table = 'fichas_tecnicas';

    protected $fillable = [
        'equipo_id',
        'producto_id',
        'paquete_id',
        // El equipo que describe, con el catálogo: con esto se liga sola al
        // producto que coincide.
        'equipment_type_id',
        'subtype_id',
        'brand_id',
        'equipment_model_id',
        'titulo',
        'archivo',
        'contenido',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class);
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    public function subtipo(): BelongsTo
    {
        return $this->belongsTo(Subtype::class, 'subtype_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(EquipmentModel::class, 'equipment_model_id');
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(FichaObservacion::class, 'ficha_tecnica_id')->latest();
    }

    /** "ENDOSCOPIA · GASTROSCOPIO · OLYMPUS GIF-H190", o null sin catálogo. */
    public function catalogoTexto(): ?string
    {
        $equipo = collect([$this->tipoEquipo?->name, $this->subtipo?->name])->filter()->implode(' · ');
        $marcaModelo = trim(($this->marca?->name ?? '').' '.($this->modelo?->name ?? ''));

        return collect([$equipo, $marcaModelo])->filter()->implode(' · ') ?: null;
    }

    /** Nombre legible de lo que tenga relacionado (producto, paquete o su catálogo). */
    public function nombreRelacionado(): ?string
    {
        if ($this->producto) {
            return trim($this->producto->marca.' '.$this->producto->modelo) ?: null;
        }

        if ($this->paquete) {
            return $this->paquete->nombre;
        }

        return $this->modelo ? trim(($this->marca?->name ?? '').' '.$this->modelo->name) : null;
    }
}
