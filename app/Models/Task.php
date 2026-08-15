<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable = [
        'title',
        'description',
        'task_description',
        'delivery_link',
        'status',
        'priority',
        'tags',
        'category',
        'reviewer_id',
        'platform',
        'has_video',
        'due_date',
        'review_date',
        'progress',
        'user_id',
        'linked_piece',
        'rejection_comment',
        'project_image',
        'created_by',
        'approval_checklist',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'platform' => 'array',
            'has_video' => 'boolean',
            'due_date' => 'date',
            'review_date' => 'date',
            'progress' => 'integer',
            'approval_checklist' => 'array',
        ];
    }

    /**
     * Responsable asignado.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario que creó la tarea.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Revisor asignado.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
