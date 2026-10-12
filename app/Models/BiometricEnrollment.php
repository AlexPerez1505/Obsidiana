<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Solicitud de RH para registrar una huella en un lector concreto. */
class BiometricEnrollment extends Model
{
    protected $fillable = ['user_id', 'device_id', 'finger_index', 'status', 'message', 'step', 'requested_by', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function vigente(): bool
    {
        return in_array($this->status, ['pending', 'capturing'], true) && $this->expires_at->isFuture();
    }
}
