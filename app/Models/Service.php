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
        'external_recipient_user_id',
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
        'external_reception_notes',
        'external_reception_evidence',
        'external_received_at',
    ];

    protected function casts(): array
    {
        return [
            'qr_expires_at' => 'datetime',
            'customer_decision_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'mano_obra' => 'decimal:2',
            'external_reception_evidence' => 'array',
            'external_received_at' => 'datetime',
        ];
    }

    /** URLs públicas de las fotos de cómo llegó el equipo al técnico externo. */
    public function externalReceptionEvidenceUrls(): array
    {
        $disco = config('filesystems.fotos_disk', 'public');

        return collect($this->external_reception_evidence ?? [])
            ->filter()
            ->map(fn (string $path) => \Illuminate\Support\Facades\Storage::disk($disco)->url($path))
            ->values()
            ->all();
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

    /** A qué cuenta de Mantenimiento Externo se le mandó este equipo. */
    public function externalRecipient()
    {
        return $this->belongsTo(User::class, 'external_recipient_user_id');
    }

    public function spareParts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ServiceSparePart::class);
    }
}
