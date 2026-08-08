<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgendaEvent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'event_type',
        'start_date',
        'end_date',
        'start_time',
        'participants',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'participants' => 'array',
    ];

    public function days(): HasMany
    {
        return $this->hasMany(AgendaEventDay::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
