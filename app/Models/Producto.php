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
        'no_serie_base',
        'firma_path',
        'estado',
        'fecha_adquisicion',
        'user_id',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
        'fecha_adquisicion' => 'date',
        'user_id' => 'integer',
    ];

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

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
