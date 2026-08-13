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
        'internal_technician_id',
        'checklist',
        'descripcion',
        'fallas_encontradas',
        'refacciones',
        'partidas_remision',
        'envio',
        'anticipo',
        'requiere_iva',
        'subtotal',
        'descuento',
        'total',
        'descripcion_general',
        'os_pdf_path',
        'os_generated_at',
        'evidencia_1',
        'evidencia_2',
        'evidencia_3',
        'proximo_mantenimiento',
        'carta_garantia',
    ];

    protected $casts = [
        'checklist' => 'array',
        'refacciones' => 'array',
        'partidas_remision' => 'array',
        'requiere_iva' => 'boolean',
        'proximo_mantenimiento' => 'date',
        'os_generated_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
