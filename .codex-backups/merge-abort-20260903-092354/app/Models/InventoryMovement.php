<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'evidence_paths',
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
            'evidence_paths' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Las unidades (seriales) que entraron con este movimiento. */
    public function seriales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductoSerial::class);
    }

    /**
     * Unidades relacionadas con este movimiento, sin importar el tipo:
     * en una entrada son las que llegaron (inventory_movement_id); en una
     * salida son las que se vendieron en ese renglón de venta (guardado en
     * metadata al momento de descontar el stock).
     */
    public function unidadesRelacionadas(): \Illuminate\Support\Collection
    {
        if ($this->movement_type === self::TYPE_ENTRY) {
            return $this->seriales;
        }

        $ventaItemId = $this->metadata['venta_item_id'] ?? null;

        return $ventaItemId
            ? ProductoSerial::where('venta_item_id', $ventaItemId)->get()
            : collect();
    }

    /** El producto de este movimiento, cuando item_type es "producto". */
    public function producto(): ?Producto
    {
        return $this->item_type === self::ITEM_PRODUCT ? Producto::find($this->item_id) : null;
    }

    /** URLs públicas de las fotos de evidencia, listas para <img>. */
    public function evidenceUrls(): array
    {
        return collect($this->evidence_paths ?? [])
            ->map(fn ($path) => Storage::disk('public')->url($path))
            ->all();
    }

    /** Siguiente folio de movimiento: ENT-2026-0001, SAL-2026-0001... */
    public static function siguienteFolio(string $movementType): string
    {
        $prefijo = match ($movementType) {
            self::TYPE_ENTRY => 'ENT',
            self::TYPE_EXIT => 'SAL',
            default => 'MOV',
        };

        $anio = now()->year;
        $ultimo = static::withTrashed()
            ->where('folio', 'like', "{$prefijo}-{$anio}-%")
            ->count();

        return sprintf('%s-%d-%04d', $prefijo, $anio, $ultimo + 1);
    }

    public function estadoTono(): string
    {
        return match ($this->movement_type) {
            self::TYPE_ENTRY => 'green',
            self::TYPE_EXIT => 'red',
            default => 'blue',
        };
    }
}
