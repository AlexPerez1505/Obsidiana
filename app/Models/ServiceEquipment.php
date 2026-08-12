<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceEquipment extends Model
{
    protected $table = 'service_equipment';

    protected $fillable = [
        'product_code',
        'service_id',
        'equipment_id',
        'equipment_type_id',
        'subtype_id',
        'brand_id',
        'equipment_model_id',
        'type_text',
        'subtype_text',
        'brand_text',
        'model_text',
        'serial_number',
        'description',
        'observations',
        'evidence_1_path',
        'evidence_2_path',
        'evidence_3_path',
        'video_path',
    ];
}
