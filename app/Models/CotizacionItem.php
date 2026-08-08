<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionItem extends Model
{
    protected $table = 'cotizacion_items';

    protected $fillable = [
        'cotizacion_id',
        'producto_id',
        'paquete_id',
        'nombre',
        'cantidad',
        'precio_original',
        'sobreprecio',
        'precio_final',
        'es_regalo',
        'subtotal_linea',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_original' => 'decimal:2',
        'sobreprecio' => 'decimal:2',
        'precio_final' => 'decimal:2',
        'es_regalo' => 'boolean',
        'subtotal_linea' => 'decimal:2',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }
}
