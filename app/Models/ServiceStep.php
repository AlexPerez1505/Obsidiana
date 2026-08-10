<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceStep extends Model
{
    protected $fillable = [
        'service_type',
        'name',
        'slug',
        'purpose',
        'order',
        'description',
        'requires_qr',
        'requires_approval',
        'is_final',
    ];
}
