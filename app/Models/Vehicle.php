<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number', 'vin', 'brand', 'model', 'year', 'color',
        'photos', 'circulation_card_doc', 'verification_doc',
        'tenancy_doc', 'insurance_doc', 'insurance_policy_number', 'status',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public const STATUS_ACTIVE      = 'active';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_INACTIVE    = 'inactive';
}
