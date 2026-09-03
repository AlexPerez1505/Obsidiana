<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaItem extends Model
{
    protected $table = 'venta_items';

    protected $fillable = [
        'venta_id', 'equipo_id', 'paquete_id', 'producto_id', 'tipo_item',
        'nombre', 'modelo', 'marca', 'imagen',
        'cantidad', 'no_series', 'precio_unitario', 'sobreprecio', 'es_regalo', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'sobreprecio' => 'decimal:2',
            'es_regalo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function seriales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductoSerial::class);
    }

    public function importe(): float
    {
        if ($this->es_regalo) {
            return 0.0;
        }

        return ((float) $this->precio_unitario + (float) $this->sobreprecio) * (int) $this->cantidad;
    }
}
