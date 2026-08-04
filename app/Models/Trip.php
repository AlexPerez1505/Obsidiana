<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'vehicle_id', 'vehicle_name', 'place',
        'status', 'total', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'total'      => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TripExpense::class);
    }

    public function getTotalComputedAttribute(): float
    {
        return (float) $this->expenses()->sum('amount');
    }
}
