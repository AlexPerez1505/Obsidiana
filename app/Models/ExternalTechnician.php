<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalTechnician extends Model
{
    /** @use HasFactory<\Database\Factories\ExternalTechnicianFactory> */
    use HasFactory;

    protected $table = 'tecnico_externo';

    protected $fillable = [
        'nombre',
        'apellidos',
        'telefono',
        'domicilio',
        'correo',
        'especialidad',
        'empresa',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
