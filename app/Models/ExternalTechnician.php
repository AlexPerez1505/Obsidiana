<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalTechnician extends Model
{
    /** @use HasFactory<\Database\Factories\ExternalTechnicianFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'company',
        'specialty',
        'address',
        'location',
        'description',
        'photo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
