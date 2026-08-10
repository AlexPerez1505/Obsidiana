<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTracking extends Model
{
    protected $fillable = [
        'service_id',
        'service_step_id',
        'status',
        'performed_by',
        'qr_token',
        'qr_expires_at',
        'notes',
        'evidence_1_path',
        'evidence_2_path',
        'evidence_3_path',
        'video_path',
        'signature',
        'started_at',
        'finished_at',
    ];

    public function serviceStep()
    {
        return $this->belongsTo(ServiceStep::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    protected function casts(): array
    {
        return [
            'qr_expires_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
