<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePermissionRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'folio',
        'user_id',
        'reviewed_by',
        'permission_date',
        'start_time',
        'end_time',
        'permission_type',
        'reason',
        'status',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'permission_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
