<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceStep extends Model
{
    protected $fillable = [
        'service_type',
        'name',
        'code',
        'order',
        'requires_qr',
        'requires_signature',
        'is_final',
    ];
}
