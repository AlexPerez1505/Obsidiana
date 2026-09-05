<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentReport extends Model
{
    protected $fillable = [
        'service_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'equipment_type',
        'equipment_subtype',
        'equipment_brand',
        'equipment_model',
        'serial_number',
        'description',
        'observations',
        'technician_name',
        'report',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
