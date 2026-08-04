<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaEventDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_event_id',
        'event_date',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(AgendaEvent::class, 'agenda_event_id');
    }
}
