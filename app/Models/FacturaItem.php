<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaItem extends Model
{
    protected $table = 'factura_items';

    protected $fillable = [
        'factura_id', 'nombre', 'modelo', 'marca',
        'cantidad', 'precio_unitario', 'sobreprecio', 'es_regalo', 'orden',
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

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function importe(): float
    {
        if ($this->es_regalo) {
            return 0.0;
        }

        return ((float) $this->precio_unitario + (float) $this->sobreprecio) * (int) $this->cantidad;
    }
}
