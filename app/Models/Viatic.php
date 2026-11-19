<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Viatic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'vehicle_id', 'vehicle_name', 'place',
        'tolls', 'fuel', 'meals', 'lodging', 'additional',
        'start_date', 'end_date', 'state', 'external_companions', 'money_given',
        'fixed_fund', 'shortfall_payer_user_id',
        'description', 'ticket_photo', 'ticket_photos', 'expense_date', 'status',
    ];

    protected $casts = [
        'tolls'         => 'decimal:2',
        'fuel'          => 'decimal:2',
        'meals'         => 'decimal:2',
        'lodging'       => 'decimal:2',
        'additional'    => 'decimal:2',
        'expense_date'  => 'date',
        'start_date'    => 'date',
        'end_date'      => 'date',
        'external_companions' => 'integer',
        'money_given'   => 'boolean',
        'fixed_fund'    => 'decimal:2',
        'ticket_photos' => 'array',
    ];

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

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
        return $this->hasMany(ViaticExpense::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'viatic_participants')->withTimestamps();
    }

    public function moneyDeliveries(): HasMany
    {
        return $this->hasMany(ViaticMoneyDelivery::class);
    }

    public function shortfallPayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shortfall_payer_user_id');
    }

    public function getTotalComputedAttribute(): float
    {
        $expensesTotal = (float) $this->expenses()->sum('amount');
        if ($expensesTotal > 0) {
            return $expensesTotal;
        }
        return (float) $this->tolls + (float) $this->fuel + (float) $this->meals + (float) $this->lodging + (float) $this->additional + (float) $this->fixed_fund;
    }

    public function getTotalAttribute(): string
    {
        return number_format($this->total_computed, 2);
    }

    /** URLs públicas de las fotos del ticket, en el orden en que se subieron. */
    public function ticketPhotoUrls(): array
    {
        return collect($this->ticket_photos ?? [])
            ->map(fn ($path) => Storage::disk('public')->url($path))
            ->all();
    }
}
