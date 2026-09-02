<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionItem extends Model
{
    protected $table = 'cotizacion_items';

    protected $fillable = [
        'cotizacion_id',
        'equipo_id',
        'paquete_id',
        'producto_id',
        'tipo_item',
        'nombre',
        'modelo',
        'marca',
        'imagen',
        'cantidad',
        'precio_unitario',
        'sobreprecio',
        'es_regalo',
        'orden',
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

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Importe del renglón (0 si es regalo).
     */
    public function importe(): float
    {
        if ($this->es_regalo) {
            return 0.0;
        }

        return ((float) $this->precio_unitario + (float) $this->sobreprecio) * (int) $this->cantidad;
    }
}
