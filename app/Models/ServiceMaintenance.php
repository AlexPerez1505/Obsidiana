<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceMaintenance extends Model
{
    protected $fillable = [
        'service_id',
        'tipo_mantenimiento',
        'tipo_reparacion',
        'tecnico_externo_id',
        'checklist',
        'descripcion',
        'fallas_encontradas',
        'refacciones',
        'evidencia_1',
        'evidencia_2',
        'evidencia_3',
        'proximo_mantenimiento',
        'carta_garantia',
    ];

    protected $casts = [
        'checklist' => 'array',
        'refacciones' => 'array',
        'proximo_mantenimiento' => 'date',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
