<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'code',
        'name',
        'equipment_type_id',
        'subtype_id',
        'brand_id',
        'equipment_model_id',
        'serial_number',
        'description',
        'stock_current',
        'stock_max',
        'stock_min',
        'warehouse',
        'assigned_to',
        'department',
        'service_date',
        'next_maintenance',
        'notes',
        'voltage',
        'frequency',
        'power',
        'weight',
        'dimensions',
        'color',
        'technical_specs',
        'supplier',
        'contact',
        'phone',
        'email',
        'invoice_number',
        'invoice_date',
        'image_path',
        'status',
        'thumb',
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_maintenance' => 'date',
        'invoice_date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    public function subtype(): BelongsTo
    {
        return $this->belongsTo(Subtype::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function equipmentModel(): BelongsTo
    {
        return $this->belongsTo(EquipmentModel::class);
    }
}
