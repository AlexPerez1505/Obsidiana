<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number', 'vin', 'brand', 'model', 'year', 'color',
        'engine_type', 'fuel_type', 'load_capacity',
        'mileage', 'fuel_efficiency', 'tank_cost',
        'acquisition_date', 'last_maintenance', 'next_maintenance',
        'last_verification', 'next_verification',
        'photos', 'circulation_card_doc', 'verification_doc',
        'tenancy_doc', 'insurance_doc', 'status',
    ];

    protected $casts = [
        'photos'           => 'array',
        'acquisition_date' => 'date',
        'last_maintenance' => 'date',
        'next_maintenance' => 'date',
        'last_verification'=> 'date',
        'next_verification'=> 'date',
        'load_capacity'    => 'decimal:2',
        'fuel_efficiency'  => 'decimal:2',
        'tank_cost'        => 'decimal:2',
    ];

    public const STATUS_ACTIVE      = 'active';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_INACTIVE    = 'inactive';
}
