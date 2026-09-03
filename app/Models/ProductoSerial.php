<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductoSerial extends Model
{
    protected $table = 'producto_seriales';

    protected $fillable = [
        'producto_id',
        'no_serie',
        'foto_path',
        'vendido',
        'vendido_en',
        'venta_item_id',
        'inventory_movement_id',
        'capturado_por',
        'editado_por',
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

    /** Quién capturó esta unidad al momento de la entrada. */
    public function capturadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }

    /** Quién hizo la última corrección al serial o la foto de esta unidad. */
    public function editadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    /** URL pública de la foto individual de esta unidad, lista para <img>. */
    public function fotoUrl(): ?string
    {
        return $this->foto_path
            ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->foto_path)
            : null;
    }
}
