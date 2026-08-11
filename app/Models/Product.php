<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'Activo';
    public const STATUS_MAINTENANCE = 'Mantenimiento';
    public const STATUS_INACTIVE = 'Inactivo';

    protected $fillable = [
        'code',
        'serial_number',
        'name',
        'category',
        'subtype',
        'unit',
        'brand',
        'model',
        'description',
        'price',
        'stock_current',
        'stock_max',
        'stock_min',
        'warehouse',
        'type',
        'technical_category',
        'specifications',
        'supplier',
        'supplier_code',
        'location',
        'warranty',
        'notes',
        'status',
        'thumb',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_current' => 'integer',
            'stock_max' => 'integer',
            'stock_min' => 'integer',
        ];
    }

    public static function nextCode(): string
    {
        $next = ((int) static::withTrashed()->max('id')) + 1;

        do {
            $code = 'PRO-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (
            static::withTrashed()
                ->where('code', $code)
                ->orWhere('serial_number', $code)
                ->exists()
        );

        return $code;
    }

    public function getToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_MAINTENANCE => 'blue',
            self::STATUS_INACTIVE => 'red',
            default => 'green',
        };
    }

    public function toTableRow(): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'subtype' => $this->subtype,
            'unit' => $this->unit,
            'price' => $this->price,
            'stock' => $this->stock_current,
            'status' => $this->status,
            'tone' => $this->tone,
            'thumb' => $this->thumb,
            'image_path' => $this->image_path,
        ];
    }

    public function toFormData(): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'subtype' => $this->subtype,
            'unit' => $this->unit,
            'brand' => $this->brand,
            'model' => $this->model,
            'description' => $this->description,
            'price' => $this->price,
            'stock_current' => $this->stock_current,
            'stock_max' => $this->stock_max,
            'stock_min' => $this->stock_min,
            'warehouse' => $this->warehouse,
            'type' => $this->type,
            'technical_category' => $this->technical_category,
            'specifications' => $this->specifications,
            'supplier' => $this->supplier,
            'supplier_code' => $this->supplier_code,
            'location' => $this->location,
            'warranty' => $this->warranty,
            'notes' => $this->notes,
            'status' => $this->status,
            'thumb' => $this->thumb,
            'image_path' => $this->image_path,
        ];
    }
}
