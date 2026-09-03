<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoSerial extends Model
{
    protected $table = 'producto_seriales';

    protected $fillable = [
        'producto_id',
        'no_serie',
        'vendido',
        'vendido_en',
        'venta_item_id',
        'inventory_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'vendido' => 'boolean',
            'vendido_en' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function ventaItem(): BelongsTo
    {
        return $this->belongsTo(VentaItem::class);
    }

    /** La entrada de inventario (con su evidencia) que trajo esta unidad. */
    public function entrada(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }
}
