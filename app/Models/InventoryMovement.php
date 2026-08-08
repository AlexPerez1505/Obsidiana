<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryMovementFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_ENTRY = 'entrada';
    public const TYPE_EXIT = 'salida';
    public const TYPE_TRANSFER = 'transferencia';

    public const ITEM_PRODUCT = 'producto';
    public const ITEM_EQUIPMENT = 'equipo';

    protected $fillable = [
        'folio',
        'movement_type',
        'item_type',
        'item_id',
        'item_code',
        'item_name',
        'warehouse',
        'destination_warehouse',
        'quantity',
        'unit',
        'stock_before',
        'stock_after',
        'reference',
        'supplier',
        'movement_date',
        'notes',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'item_id' => 'integer',
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'movement_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
