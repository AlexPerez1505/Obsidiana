<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeReport extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeReportFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_ATTENDANCE = 'asistencia';
    public const TYPE_ABSENCE = 'falta';
    public const TYPE_VACATION = 'vacaciones';
    public const TYPE_PERMISSION = 'permiso';
    public const TYPE_INCIDENT = 'incidencia';

    public const STATUS_PENDING = 'pendiente';
    public const STATUS_VALIDATED = 'validada';
    public const STATUS_APPROVED = 'aprobada';
    public const STATUS_JUSTIFY = 'justificar';
    public const STATUS_REVIEW = 'revision';
    public const STATUS_REJECTED = 'rechazada';
    public const STATUS_CANCELLED = 'cancelada';

    protected $fillable = [
        'user_id',
        'created_by',
        'type',
        'status',
        'area',
        'start_date',
        'end_date',
        'check_in',
        'check_out',
        'late_minutes',
        'detail',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'late_minutes' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
