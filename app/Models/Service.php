<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'service_number',
        'customer_id',
        'service_type',
        'internal_technician_id',
        'external_technician_id',
        'registered_by',
        'current_step_id',
        'qr_token',
        'qr_expires_at',
        'signature',
        'status',
        'customer_decision',
        'customer_decision_at',
        'mano_obra',
        'started_at',
        'finished_at',
        'garantia_documento_id',
        'documentos',
        'reporte_data',
    ];

    protected function casts(): array
    {
        return [
            'qr_expires_at' => 'datetime',
            'customer_decision_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'mano_obra' => 'decimal:2',
            'documentos' => 'array',
            'reporte_data' => 'array',
        ];
    }

    public function garantiaDocumento()
    {
        return $this->belongsTo(GarantiaDocumento::class);
    }

    public function serviceEquipment()
    {
        return $this->hasOne(ServiceEquipment::class);
    }

    public function serviceTrackings()
    {
        return $this->hasMany(ServiceTracking::class);
    }

    public function currentStep()
    {
        return $this->belongsTo(ServiceStep::class, 'current_step_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function internalTechnician()
    {
        return $this->belongsTo(User::class, 'internal_technician_id');
    }

    /** Quien registró la orden (o quien mandó la invitación, si llegó por enlace). */
    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function externalTechnician()
    {
        return $this->belongsTo(ExternalTechnician::class, 'external_technician_id');
    }

    public function spareParts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ServiceSparePart::class);
    }
}
