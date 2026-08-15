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
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'qr_expires_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
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

    public function externalTechnician()
    {
        return $this->belongsTo(ExternalTechnician::class, 'external_technician_id');
    }

    public function maintenance()
    {
        return $this->hasOne(ServiceMaintenance::class);
    }

    /**
     * Obtener el código de verificación del paso 6 (notificacion-llegada-tecnico)
     */
    public function getVerificationCode()
    {
        return $this->serviceTrackings()
            ->whereHas('serviceStep', fn($q) => $q->where('slug', 'notificacion-llegada-tecnico'))
            ->value('verification_code');
    }
}
