<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'equipment_type_id',
        'subtype_id',
        'brand_id',
        'equipment_model_id',
        'tipo_equipo',
        'subtipo',
        'marca',
        'modelo',
        'precio',
        'imagen_path',
        'stock',
        'descripcion',
        'proveedor',
        'no_serie',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Las columnas de texto se llenan solas desde el catalogo.
     *
     * Cotizaciones, facturas, paquetes y servicios leen $producto->marca y
     * companía directamente, y los PDF ya emitidos deben conservar el nombre
     * que tenia el equipo ese dia. Por eso el texto se guarda ademas del id.
     */
    protected static function booted(): void
    {
        static::saving(function (Producto $producto) {
            $producto->sincronizarNombresDeCatalogo();
        });
    }

    public function sincronizarNombresDeCatalogo(): void
    {
        $mapa = [
            'equipment_type_id' => ['tipo_equipo', EquipmentType::class],
            'subtype_id' => ['subtipo', Subtype::class],
            'brand_id' => ['marca', Brand::class],
            'equipment_model_id' => ['modelo', EquipmentModel::class],
        ];

        foreach ($mapa as $llave => [$columnaTexto, $clase]) {
            if (! $this->{$llave}) {
                continue;
            }

            // Solo se consulta cuando la llave cambio, para no pegarle a la
            // base en cada guardado.
            if (! $this->isDirty($llave) && $this->{$columnaTexto}) {
                continue;
            }

            $registro = $clase::find($this->{$llave});

            if ($registro) {
                $this->{$columnaTexto} = $registro->name;
            }
        }
    }

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    public function subtype(): BelongsTo
    {
        return $this->belongsTo(Subtype::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function equipmentModel(): BelongsTo
    {
        return $this->belongsTo(EquipmentModel::class);
    }

    public function paquetes(): BelongsToMany
    {
        return $this->belongsToMany(Paquete::class, 'paquete_producto')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'producto_id');
    }
}
