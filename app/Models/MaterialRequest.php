<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'borrador';
    public const STATUS_PENDING = 'pendiente';
    public const STATUS_APPROVED = 'aprobada';
    public const STATUS_REJECTED = 'rechazada';
    public const STATUS_DELIVERED = 'entregada';

    protected $fillable = [
        'folio',
        'category',
        'material_name',
        'quantity',
        'unit',
        'required_date',
        'urgency',
        'justification',
        'status',
        'requested_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'approved_quantity',
        'delivered_by',
        'delivered_at',
        'delivery_notes',
        'metadata',
    ];

    protected $casts = [
        'required_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }
}
